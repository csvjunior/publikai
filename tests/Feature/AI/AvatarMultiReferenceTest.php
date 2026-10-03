<?php

namespace Tests\Feature\AI;

use App\Enums\ContentScriptStatus;
use App\Enums\ImageGenerationRequestStatus;
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
use App\Services\AvatarReferenceService;
use App\Services\ImageGenerationService;
use App\Services\VisualPromptBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Multi-reference do Avatar (Sprint 5.5.3): limite, primary, remoção com
 * promoção, snapshot múltiplo, seleção humana, provider multi-image,
 * failures e segurança.
 */
class AvatarMultiReferenceTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    private function png600(): string
    {
        return substr_replace(
            (string) base64_decode(self::PNG_1X1),
            pack('N', 600).pack('N', 600),
            16,
            8
        );
    }

    private function jpg600(): string
    {
        return (string) hex2bin('ffd8ffc0000b080258025801011100ffd9');
    }

    private function upload(string $binary, string $name = 'foto.png'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'mref').'_'.$name;
        file_put_contents($path, $binary);

        return new UploadedFile($path, $name, null, UPLOAD_ERR_OK, true);
    }

    private function avatar(): Avatar
    {
        return Avatar::factory()->create(['language' => 'en-US', 'market' => 'US']);
    }

    private function context(?Avatar $avatar = null): ContentScript
    {
        return ContentScript::factory()->create([
            'product_id' => Product::factory()->create(['language' => 'en-US', 'market' => 'US'])->id,
            'content_blueprint_id' => ContentBlueprint::factory()->create(['language' => 'en-US', 'market' => 'US'])->id,
            'persona_id' => Persona::factory()->create(['language' => 'en-US', 'market' => 'US'])->id,
            'avatar_id' => ($avatar ?? $this->avatar())->id,
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
            'id' => 'interaction-multi-1',
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

    // ---- limite / primary / ordem ----

    public function test_primeira_vira_primary_outras_nao(): void
    {
        Storage::fake('public');
        $avatar = $this->avatar();
        $service = app(AvatarReferenceService::class);

        $a = $service->attach($avatar, $this->upload($this->png600()));
        $b = $service->attach($avatar, $this->upload($this->jpg600(), 'b.jpg'));
        $c = $service->attach($avatar, $this->upload($this->png600(), 'c.png'));

        $this->assertSame($a->id, $avatar->primaryReferenceImage()->id);
        $this->assertSame([$a->id, $b->id, $c->id], $avatar->fresh()->referenceImages()->pluck('media_assets.id')->all());
        $this->assertSame(1, (int) DB::table('avatar_reference_media_assets')->where('avatar_id', $avatar->id)->where('is_primary', true)->count());
    }

    public function test_quinta_bloqueada(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $avatar = $this->avatar();
        $service = app(AvatarReferenceService::class);

        for ($i = 0; $i < 4; $i++) {
            $service->attach($avatar, $this->upload($this->png600(), "r{$i}.png"));
        }

        $this->actingAs($user)->post(route('avatars.reference.store', $avatar), [
            'image' => $this->upload($this->png600(), 'extra.png'),
        ])->assertSessionHasErrors('image');

        $this->assertSame(4, $avatar->fresh()->referenceImages()->count());
    }

    public function test_trocar_primary(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $avatar = $this->avatar();
        $service = app(AvatarReferenceService::class);

        $a = $service->attach($avatar, $this->upload($this->png600()));
        $b = $service->attach($avatar, $this->upload($this->jpg600(), 'b.jpg'));

        $this->actingAs($user)
            ->post(route('avatars.references.primary', [$avatar, $b]))
            ->assertRedirect(route('avatars.show', $avatar));

        $this->assertSame($b->id, $avatar->primaryReferenceImage()->id);
        $this->assertSame(1, (int) DB::table('avatar_reference_media_assets')->where('avatar_id', $avatar->id)->where('is_primary', true)->count());
        $this->assertFalse((bool) DB::table('avatar_reference_media_assets')->where(['avatar_id' => $avatar->id, 'media_asset_id' => $a->id])->value('is_primary'));
    }

    public function test_primary_de_outro_avatar_rejeitada(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $other = $this->avatar();
        $asset = app(AvatarReferenceService::class)->attach($other, $this->upload($this->png600()));

        $this->actingAs($user)
            ->post(route('avatars.references.primary', [$this->avatar(), $asset]))
            ->assertNotFound();
    }

    // ---- remove ----

    public function test_remover_auxiliar(): void
    {
        Storage::fake('public');
        $avatar = $this->avatar();
        $service = app(AvatarReferenceService::class);

        $a = $service->attach($avatar, $this->upload($this->png600()));
        $b = $service->attach($avatar, $this->upload($this->jpg600(), 'b.jpg'), null);

        $service->remove($avatar, $b);

        $this->assertSame([$a->id], $avatar->fresh()->referenceImages()->pluck('media_assets.id')->all());
        $this->assertSame($a->id, $avatar->primaryReferenceImage()->id);
        $this->assertDatabaseMissing('media_assets', ['id' => $b->id]);
    }

    public function test_remover_primary_promove_proxima(): void
    {
        Storage::fake('public');
        $avatar = $this->avatar();
        $service = app(AvatarReferenceService::class);

        $a = $service->attach($avatar, $this->upload($this->png600()));
        $b = $service->attach($avatar, $this->upload($this->jpg600(), 'b.jpg'), null);
        $c = $service->attach($avatar, $this->upload($this->png600(), 'c.png'), null);

        $service->remove($avatar, $a);

        $this->assertSame([$b->id, $c->id], $avatar->fresh()->referenceImages()->pluck('media_assets.id')->all());
        $this->assertSame($b->id, $avatar->primaryReferenceImage()->id);
        $this->assertDatabaseMissing('media_assets', ['id' => $a->id]);
    }

    public function test_remover_ultima_zera(): void
    {
        Storage::fake('public');
        $avatar = $this->avatar();
        $service = app(AvatarReferenceService::class);

        $a = $service->attach($avatar, $this->upload($this->png600()));
        $service->remove($avatar, $a);

        $this->assertSame(0, $avatar->fresh()->referenceImages()->count());
        $this->assertNull($avatar->primaryReferenceImage());
    }

    public function test_remover_compartilhado_preserva(): void
    {
        Storage::fake('public');
        $avatar = $this->avatar();
        $service = app(AvatarReferenceService::class);

        $a = $service->attach($avatar, $this->upload($this->png600()));
        ImageGenerationRequest::factory()->create();
        $req = ImageGenerationRequest::firstOrFail();
        $req->referenceImages()->attach($a->id, ['position' => 1]);

        $service->remove($avatar, $a);

        $this->assertSame(0, $avatar->fresh()->referenceImages()->count());
        $this->assertDatabaseHas('media_assets', ['id' => $a->id]);
        Storage::disk('public')->assertExists($a->path);
    }

    // ---- snapshot ----

    public function test_snapshot_multiplo_imutavel(): void
    {
        Storage::fake('public');
        $avatar = $this->avatar();
        $service = app(AvatarReferenceService::class);

        $a = $service->attach($avatar, $this->upload($this->png600()));
        $b = $service->attach($avatar, $this->upload($this->jpg600(), 'b.jpg'), null);
        $c = $service->attach($avatar, $this->upload($this->png600(), 'c.png'), null);
        $script = $this->context($avatar);

        $request = app(ImageGenerationService::class)->createRequest(
            'Vertical photorealistic UGC scene with neutral daylight.',
            ['content_script_id' => $script->id, 'reference_media_asset_ids' => [$a->id, $b->id, $c->id]],
            null,
        );

        $this->assertSame([$a->id, $b->id, $c->id], $request->referenceImages()->pluck('media_assets.id')->all());

        // Avatar muda depois: remove B, troca primary, adiciona D.
        $service->remove($avatar, $b);
        $service->markPrimary($avatar, $c);
        $d = $service->attach($avatar, $this->upload($this->jpg600(), 'd.jpg'), null);

        $this->assertSame([$a->id, $b->id, $c->id], $request->fresh()->referenceImages()->pluck('media_assets.id')->all());
        $this->assertSame([$a->id, $c->id, $d->id], $avatar->fresh()->referenceImages()->pluck('media_assets.id')->all());
    }

    // ---- seleção humana via POST ----

    public function test_post_default_usa_todas(): void
    {
        Storage::fake('public');
        $this->enableImage();
        $user = User::factory()->create();
        $avatar = $this->avatar();
        $service = app(AvatarReferenceService::class);
        $a = $service->attach($avatar, $this->upload($this->png600()));
        $b = $service->attach($avatar, $this->upload($this->jpg600(), 'b.jpg'), null);
        $script = $this->context($avatar);

        $this->actingAs($user)->post(route('scripts.images.store', $script), $this->validPost());

        $request = ImageGenerationRequest::firstOrFail();
        $this->assertSame([$a->id, $b->id], $request->referenceImages()->pluck('media_assets.id')->all());
    }

    public function test_post_selecao_parcial(): void
    {
        Storage::fake('public');
        $this->enableImage();
        $user = User::factory()->create();
        $avatar = $this->avatar();
        $service = app(AvatarReferenceService::class);
        $service->attach($avatar, $this->upload($this->png600()));
        $b = $service->attach($avatar, $this->upload($this->jpg600(), 'b.jpg'), null);
        $script = $this->context($avatar);

        // Só a auxiliar B: primary não é obrigatória.
        $this->actingAs($user)->post(
            route('scripts.images.store', $script),
            $this->validPost(['reference_ids' => [$b->id]])
        )->assertRedirect(route('scripts.show', $script));

        $request = ImageGenerationRequest::firstOrFail();
        $this->assertSame([$b->id], $request->referenceImages()->pluck('media_assets.id')->all());
    }

    public function test_post_visual_dna_only(): void
    {
        Storage::fake('public');
        $this->enableImage();
        $user = User::factory()->create();
        $avatar = $this->avatar();
        app(AvatarReferenceService::class)->attach($avatar, $this->upload($this->png600()));
        $script = $this->context($avatar);

        $this->actingAs($user)->post(
            route('scripts.images.store', $script),
            $this->validPost(['visual_dna_only' => '1'])
        )->assertRedirect(route('scripts.show', $script));

        $this->assertSame(0, ImageGenerationRequest::firstOrFail()->referenceImages()->count());
    }

    public function test_post_referencia_estrangeira_rejeitada(): void
    {
        Storage::fake('public');
        $this->enableImage();
        $user = User::factory()->create();
        $script = $this->context();
        $foreign = MediaAsset::factory()->create();

        $this->actingAs($user)->post(
            route('scripts.images.store', $script),
            $this->validPost(['reference_ids' => [$foreign->id]])
        )->assertStatus(422);

        $this->assertDatabaseCount('image_generation_requests', 0);
    }

    // ---- provider multi ----

    public function test_provider_multi_image_ordem(): void
    {
        Storage::fake('public');
        $this->enableImage();
        $this->fakeImage();
        $avatar = $this->avatar();
        $service = app(AvatarReferenceService::class);
        $binPng = $this->png600();
        $binJpg = $this->jpg600();
        $a = $service->attach($avatar, $this->upload($binPng));
        $b = $service->attach($avatar, $this->upload($binJpg, 'b.jpg'), null);
        $script = $this->context($avatar);

        $request = app(ImageGenerationService::class)->createRequest(
            'Vertical photorealistic UGC scene with neutral daylight.',
            ['content_script_id' => $script->id, 'reference_media_asset_ids' => [$a->id, $b->id]],
            null,
        );

        (new GenerateImageJob($request->id))->handle(app(ImageGenerationService::class));

        $this->assertSame(ImageGenerationRequestStatus::Success, $request->fresh()->status);

        Http::assertSent(function ($http) use ($binPng, $binJpg) {
            $input = $http->data()['input'] ?? null;

            return is_array($input)
                && count($input) === 3
                && ($input[0]['type'] ?? null) === 'text'
                && ($input[1]['mime_type'] ?? null) === 'image/png'
                && base64_decode($input[1]['data'] ?? '', true) === $binPng
                && ($input[2]['mime_type'] ?? null) === 'image/jpeg'
                && base64_decode($input[2]['data'] ?? '', true) === $binJpg;
        });

        $log = AiGeneration::firstWhere('operation', 'image_generation');
        $this->assertTrue((bool) $log->metadata['reference_used']);
        $this->assertSame(2, $log->metadata['reference_count']);
    }

    // ---- failures multi ----

    public function test_uma_missing_falha_tudo(): void
    {
        Storage::fake('public');
        $this->enableImage();
        $this->fakeImage();
        $avatar = $this->avatar();
        $service = app(AvatarReferenceService::class);
        $a = $service->attach($avatar, $this->upload($this->png600()));
        $b = $service->attach($avatar, $this->upload($this->jpg600(), 'b.jpg'), null);
        $script = $this->context($avatar);

        $request = app(ImageGenerationService::class)->createRequest(
            'Vertical photorealistic UGC scene with neutral daylight.',
            ['content_script_id' => $script->id, 'reference_media_asset_ids' => [$a->id, $b->id]],
            null,
        );

        Storage::disk('public')->delete($b->path);

        (new GenerateImageJob($request->id))->handle(app(ImageGenerationService::class));

        $request = $request->fresh();
        $this->assertSame(ImageGenerationRequestStatus::Failed, $request->status);
        $this->assertSame('reference_missing', $request->error_code);
        Http::assertNothingSent();
    }

    public function test_prompt_plural(): void
    {
        $script = $this->context();

        $prompt = app(VisualPromptBuilder::class)->build(
            $script, $script->product, $script->blueprint, $script->persona, $script->avatar, 3,
        );

        $this->assertStringContainsString('reference images together', $prompt);
    }

    // ---- UI ----

    public function test_avatar_show_grid_e_limite(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $avatar = $this->avatar();
        $service = app(AvatarReferenceService::class);
        for ($i = 0; $i < 4; $i++) {
            $service->attach($avatar, $this->upload($this->png600(), "r{$i}.png"));
        }

        $this->withoutVite()->actingAs($user)->get(route('avatars.show', $avatar->fresh()))
            ->assertOk()
            ->assertSee('Referências visuais', false)
            ->assertSee('4 de 4 referências', false)
            ->assertSee('Limite de referências atingido', false)
            ->assertSee('Principal', false);
    }

    public function test_script_create_checklist(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $avatar = $this->avatar();
        $service = app(AvatarReferenceService::class);
        $service->attach($avatar, $this->upload($this->png600()));
        $service->attach($avatar, $this->upload($this->jpg600(), 'b.jpg'), null);
        $script = $this->context($avatar);

        $this->withoutVite()->actingAs($user)->get(route('scripts.images.create', $script->fresh()))
            ->assertOk()
            ->assertSee('Referências visuais usadas', false)
            ->assertSee('Gerar somente com Visual DNA', false)
            ->assertSee('reference_ids', false)
            ->assertSee('visual-dna-only', false)
            ->assertSee('data-reference-checkbox', false)
            ->assertSee('data-reference-card', false)
            ->assertSee('visual-dna-only-helper', false);
    }
}
