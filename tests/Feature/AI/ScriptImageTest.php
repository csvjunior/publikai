<?php

namespace Tests\Feature\AI;

use App\Enums\ContentScriptStatus;
use App\Enums\ImageGenerationRequestStatus;
use App\Enums\UserRole;
use App\Jobs\GenerateImageJob;
use App\Models\AiGeneration;
use App\Models\Avatar;
use App\Models\ContentBlueprint;
use App\Models\ContentScript;
use App\Models\ImageGenerationRequest;
use App\Models\MediaAsset;
use App\Models\Persona;
use App\Models\Product;
use App\Models\User;
use App\Services\ImageGenerationService;
use App\Services\VisualPromptBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScriptImageTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    private function context(): ContentScript
    {
        $product = Product::factory()->create(['language' => 'en-US', 'market' => 'US']);
        $blueprint = ContentBlueprint::factory()->create(['language' => 'en-US', 'market' => 'US']);
        $persona = Persona::factory()->create(['language' => 'en-US', 'market' => 'US']);
        $avatar = Avatar::factory()->create(['language' => 'en-US', 'market' => 'US']);

        return ContentScript::factory()->create([
            'product_id' => $product->id,
            'content_blueprint_id' => $blueprint->id,
            'persona_id' => $persona->id,
            'avatar_id' => $avatar->id,
            'status' => ContentScriptStatus::Ready,
        ]);
    }

    private function enableImage(): void
    {
        config()->set('ai.google.image.enabled', true);
        config()->set('ai.google.auth_key', 'test-auth-key');
    }

    private function fakeImage(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'id' => 'interaction-ctx-1',
            'steps' => [
                ['type' => 'model_output', 'content' => [['type' => 'image', 'data' => self::PNG_1X1, 'mime_type' => 'image/png']]],
            ],
        ], 200)]);
    }

    private function validPost(array $overrides = []): array
    {
        return array_merge([
            'prompt' => 'Vertical photorealistic UGC scene with neutral daylight.',
            'aspect_ratio' => '9:16',
            'image_size' => '1K',
            'mime_type' => 'image/png',
            'purpose' => 'scene',
        ], $overrides);
    }

    public function test_guest_bloqueado(): void
    {
        $script = $this->context();

        $this->get(route('scripts.images.create', $script))->assertRedirect('/login');
        $this->post(route('scripts.images.store', $script))->assertRedirect('/login');
    }

    public function test_operator_permitido_no_contextual(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $script = $this->context();

        $this->actingAs($operator)->get(route('scripts.images.create', $script))->assertOk();
    }

    public function test_admin_permitido(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $script = $this->context();

        $this->withoutVite()->actingAs($admin)->get(route('scripts.images.create', $script))
            ->assertOk()
            ->assertSee('Prompt visual', false);
    }

    public function test_draft_nao_gera(): void
    {
        $user = User::factory()->create();
        $script = $this->context();
        $script->update(['status' => ContentScriptStatus::Draft]);

        $this->actingAs($user)->get(route('scripts.images.create', $script))->assertForbidden();
        $this->actingAs($user)->post(route('scripts.images.store', $script), $this->validPost())->assertForbidden();
        $this->assertDatabaseCount('image_generation_requests', 0);
    }

    public function test_ready_e_approved_geram(): void
    {
        $this->enableImage();
        Queue::fake();
        $user = User::factory()->create();

        foreach ([ContentScriptStatus::Ready, ContentScriptStatus::Approved] as $status) {
            $script = $this->context();
            $script->update(['status' => $status]);

            $this->actingAs($user)->post(route('scripts.images.store', $script), $this->validPost())
                ->assertRedirect(route('scripts.show', $script));
        }

        $this->assertSame(2, ImageGenerationRequest::count());
    }

    public function test_failed_archived_nao_geram(): void
    {
        $user = User::factory()->create();

        foreach ([ContentScriptStatus::Failed, ContentScriptStatus::Archived] as $status) {
            $script = $this->context();
            $script->update(['status' => $status]);

            $this->actingAs($user)->post(route('scripts.images.store', $script), $this->validPost())
                ->assertForbidden();
        }

        $this->assertDatabaseCount('image_generation_requests', 0);
    }

    public function test_builder_usa_contexto(): void
    {
        $script = $this->context();
        $script->update([
            'visual_direction' => 'Direcao visual teste',
            'on_screen_text' => 'Texto de tela',
        ]);

        $prompt = app(VisualPromptBuilder::class)->build(
            $script->fresh(),
            $script->product,
            $script->blueprint,
            $script->persona,
            $script->avatar,
        );

        $this->assertStringContainsString('SUBJECT', $prompt);
        $this->assertStringContainsString('Direcao visual teste', $prompt);
        $this->assertStringContainsString($script->product->name, $prompt);
        $this->assertStringContainsString($script->blueprint->visual_style ?? '', $prompt);
        $this->assertStringContainsString($script->avatar->hair ?? '', $prompt);
        $this->assertStringNotContainsString('slugs', strtolower($prompt));
    }

    public function test_persona_nao_altera_aparencia(): void
    {
        $script = $this->context();
        $script->persona->update(['tone' => 'TOM-PERSONA-XYZ']);
        $script->avatar->update(['hair' => 'CABELO-AVATAR-XYZ']);

        $prompt = app(VisualPromptBuilder::class)->build(
            $script, $script->product, $script->blueprint, $script->persona->fresh(), $script->avatar->fresh()
        );

        // Persona aparece no contexto de comunicação, nunca como aparência.
        $this->assertStringContainsString('TOM-PERSONA-XYZ', $prompt);
        $this->assertStringContainsString('CABELO-AVATAR-XYZ', $prompt);
        $this->assertStringNotContainsString('Hair: TOM-PERSONA-XYZ', $prompt);
    }

    public function test_on_screen_text_nao_vira_texto_renderizado(): void
    {
        $script = $this->context();

        $prompt = app(VisualPromptBuilder::class)->build(
            $script, $script->product, $script->blueprint, $script->persona, $script->avatar
        );

        $this->assertStringContainsString('do not render text', strtolower($prompt));
    }

    public function test_prompt_pode_ser_editado_sem_alterar_dominio(): void
    {
        $this->enableImage();
        Queue::fake();
        $user = User::factory()->create();
        $script = $this->context();

        $this->actingAs($user)->post(
            route('scripts.images.store', $script),
            $this->validPost(['prompt' => 'Um prompt totalmente manual e editado pelo humano.'])
        )->assertRedirect();

        $generation = ImageGenerationRequest::firstOrFail();
        $this->assertSame('Um prompt totalmente manual e editado pelo humano.', $generation->prompt);
        $this->assertSame($script->id, $generation->content_script_id);
    }

    public function test_request_recebe_contexto(): void
    {
        $this->enableImage();
        Queue::fake();
        $user = User::factory()->create();
        $script = $this->context();

        $this->actingAs($user)->post(
            route('scripts.images.store', $script),
            $this->validPost(['purpose' => 'cover', 'is_primary' => true])
        )->assertRedirect();

        $this->assertDatabaseHas('image_generation_requests', [
            'content_script_id' => $script->id,
            'purpose' => 'cover',
            'is_primary' => true,
        ]);
    }

    public function test_job_success_cria_pivot(): void
    {
        $this->enableImage();
        $this->fakeImage();
        Storage::fake('public');
        $script = $this->context();

        $generation = app(ImageGenerationService::class)->createRequest(
            'Um prompt contextual válido para teste.',
            ['content_script_id' => $script->id, 'purpose' => 'scene'],
            null,
        );

        (new GenerateImageJob($generation->id))->handle(app(ImageGenerationService::class));

        $generation->refresh();
        $this->assertNotNull($generation->media_asset_id);
        $this->assertTrue($script->mediaAssets()->whereKey($generation->media_asset_id)->exists());
        $this->assertSame('scene', $script->mediaAssets()->firstOrFail()->pivot->purpose);
    }

    public function test_failed_nao_cria_pivot(): void
    {
        $this->enableImage();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'x'], 503)]);
        Storage::fake('public');
        $script = $this->context();

        $generation = app(ImageGenerationService::class)->createRequest(
            'Um prompt contextual válido para teste.',
            ['content_script_id' => $script->id],
            null,
        );

        (new GenerateImageJob($generation->id))->handle(app(ImageGenerationService::class));

        $this->assertSame(0, $script->mediaAssets()->count());
        $this->assertDatabaseCount('media_assets', 0);
    }

    public function test_primary_transaction(): void
    {
        $this->enableImage();
        $this->fakeImage();
        Storage::fake('public');
        $script = $this->context();
        $service = app(ImageGenerationService::class);

        $first = $service->createRequest('Primeiro prompt contextual válido.', ['content_script_id' => $script->id], null);
        (new GenerateImageJob($first->id))->handle($service);

        $second = $service->createRequest(
            'Segundo prompt contextual válido.',
            ['content_script_id' => $script->id, 'is_primary' => true],
            null
        );
        (new GenerateImageJob($second->id))->handle($service);

        $primaries = $script->mediaAssets()->wherePivot('is_primary', true)->get();
        $this->assertSame(1, $primaries->count());
        $this->assertSame($second->fresh()->media_asset_id, $primaries->first()->id);
    }

    public function test_trocar_primary_desmarca_anterior(): void
    {
        $script = $this->context();
        $first = MediaAsset::factory()->create();
        $second = MediaAsset::factory()->create();
        $script->mediaAssets()->attach($first->id, ['purpose' => 'scene', 'is_primary' => true]);
        $script->mediaAssets()->attach($second->id, ['purpose' => 'scene', 'is_primary' => false]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('scripts.images.primary', [$script, $second]))->assertRedirect();

        $this->assertFalse((bool) $script->mediaAssets()->find($first->id)->pivot->is_primary);
        $this->assertTrue((bool) $script->mediaAssets()->find($second->id)->pivot->is_primary);
    }

    public function test_varios_assets_por_script(): void
    {
        $script = $this->context();
        $script->mediaAssets()->attach(MediaAsset::factory()->create()->id, ['purpose' => 'scene']);
        $script->mediaAssets()->attach(MediaAsset::factory()->create()->id, ['purpose' => 'cover']);

        $this->assertSame(2, $script->mediaAssets()->count());
    }

    public function test_gallery_renderiza(): void
    {
        $user = User::factory()->create();
        $script = $this->context();
        $asset = MediaAsset::factory()->create();
        $script->mediaAssets()->attach($asset->id, ['purpose' => 'scene', 'is_primary' => true]);

        $response = $this->withoutVite()->actingAs($user)->get(route('scripts.show', $script))->assertOk();

        $response->assertSee('Imagens', false);
        $response->assertSee('Principal', false);
        $response->assertSee($asset->path, false);
    }

    public function test_pending_renderiza(): void
    {
        $user = User::factory()->create();
        $script = $this->context();
        ImageGenerationRequest::factory()->create([
            'content_script_id' => $script->id,
            'status' => ImageGenerationRequestStatus::Pending,
        ]);

        $this->withoutVite()->actingAs($user)->get(route('scripts.show', $script))
            ->assertOk()
            ->assertSee('Gerações em andamento', false);
    }

    public function test_failed_renderiza(): void
    {
        $user = User::factory()->create();
        $script = $this->context();
        ImageGenerationRequest::factory()->create([
            'content_script_id' => $script->id,
            'status' => ImageGenerationRequestStatus::Failed,
            'error_code' => 'timeout',
        ]);

        $this->withoutVite()->actingAs($user)->get(route('scripts.show', $script))
            ->assertOk()
            ->assertSee('Falhas recentes', false);
    }

    public function test_provider_disabled_bloqueia(): void
    {
        config()->set('ai.google.image.enabled', false);
        $user = User::factory()->create();
        $script = $this->context();

        $response = $this->actingAs($user)->get(route('scripts.images.create', $script))->assertOk();

        $response->assertSee('Configure a geração de imagens', false);

        $this->actingAs($user)->post(route('scripts.images.store', $script), $this->validPost())
            ->assertRedirect();
        $this->assertDatabaseCount('image_generation_requests', 0);
        Http::assertNothingSent();
    }

    public function test_ai_generations_correto_e_sem_vazamento(): void
    {
        $this->enableImage();
        $this->fakeImage();
        Storage::fake('public');
        $script = $this->context();

        $generation = app(ImageGenerationService::class)->createRequest(
            'PROMPT-CONTEXTUAL-XYZ',
            ['content_script_id' => $script->id],
            null,
        );
        (new GenerateImageJob($generation->id))->handle(app(ImageGenerationService::class));

        $log = AiGeneration::firstWhere('operation', 'image_generation');
        $this->assertSame('success', $log->status->value);
        $dump = json_encode([$log->metadata, $log->error_code]);
        $this->assertStringNotContainsString('PROMPT-CONTEXTUAL-XYZ', (string) $dump);
        $this->assertStringNotContainsString('test-auth-key', (string) $dump);

        $asset = MediaAsset::firstOrFail();
        $this->assertStringNotContainsString('PROMPT-CONTEXTUAL-XYZ', (string) json_encode($asset->metadata));
    }

    public function test_gallery_apresentacao(): void
    {
        $user = User::factory()->create();
        $script = $this->context();
        $primary = MediaAsset::factory()->create();
        $other = MediaAsset::factory()->create();
        $script->mediaAssets()->attach($primary->id, ['purpose' => 'scene', 'is_primary' => true]);
        $script->mediaAssets()->attach($other->id, ['purpose' => 'product', 'is_primary' => false]);
        ImageGenerationRequest::factory()->create([
            'content_script_id' => $script->id,
            'status' => ImageGenerationRequestStatus::Pending,
        ]);
        ImageGenerationRequest::factory()->create([
            'content_script_id' => $script->id,
            'status' => ImageGenerationRequestStatus::Processing,
        ]);
        ImageGenerationRequest::factory()->create([
            'content_script_id' => $script->id,
            'status' => ImageGenerationRequestStatus::Failed,
            'error_code' => 'timeout',
        ]);

        $response = $this->withoutVite()->actingAs($user)->get(route('scripts.show', $script))->assertOk();

        // Purpose em português, nunca cru.
        $response->assertSee('Cena', false);
        $response->assertSee('Produto', false);
        $response->assertDontSee('>scene<', false);
        // Failed amigável, sem código cru.
        $response->assertSee('A geração excedeu o tempo esperado.', false);
        // Pending/processing como itens com badge.
        $response->assertSee('Pendente', false);
        $response->assertSee('Processando', false);
        // Ação de primary só no não-principal.
        $response->assertSee('Definir como principal', false);
    }
}
