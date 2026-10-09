<?php

namespace Tests\Feature\CreativeStudio;

use App\Enums\ContentScriptSource;
use App\Enums\ContentScriptStatus;
use App\Enums\UserRole;
use App\Models\Avatar;
use App\Models\ContentBlueprint;
use App\Models\ContentScript;
use App\Models\Persona;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContentScriptTest extends TestCase
{
    use RefreshDatabase;

    private function context(?string $language = 'en-US', ?string $market = 'US'): array
    {
        $product = Product::factory()->create(['language' => $language, 'market' => $market]);
        $blueprint = ContentBlueprint::factory()->create(['language' => $language, 'market' => $market]);
        $persona = Persona::factory()->create(['language' => $language, 'market' => $market]);
        $avatar = Avatar::factory()->create(['language' => $language, 'market' => $market]);

        return [$product, $blueprint, $persona, $avatar];
    }

    private function manualData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Curiosity Beauty Discovery',
            'hook' => "I didn't expect this to make such a difference...",
            'opening' => 'I kept seeing this everywhere.',
            'body' => 'Mostrar o problema e demonstrar rapidamente.',
            'cta' => 'If you want to check it out, the link is in my bio.',
            'on_screen_text' => 'I finally tried it',
            'visual_direction' => 'Natural handheld UGC.',
            'voice_direction' => 'Friendly, casual, relaxed.',
            'duration_seconds' => 15,
            'status' => ContentScriptStatus::Draft->value,
        ], $overrides);
    }

    private function aiPayload(): array
    {
        return [
            'title' => 'Curiosity Beauty Discovery',
            'hook' => 'I kept seeing this everywhere...',
            'opening' => 'Let me show you.',
            'body' => 'Problem, product, quick demo.',
            'cta' => 'Link in bio.',
            'on_screen_text' => 'Try it',
            'visual_direction' => 'Handheld close-ups.',
            'voice_direction' => 'Casual friendly.',
            'duration_seconds' => 15,
        ];
    }

    private function enableAi(): void
    {
        config()->set('ai.google.enabled', true);
        config()->set('ai.google.auth_key', 'test-auth-key');
    }

    public function test_guest_bloqueado(): void
    {
        $this->get('/scripts')->assertRedirect('/login');
        $this->post('/scripts', [])->assertRedirect('/login');
    }

    public function test_index_autenticado(): void
    {
        $user = User::factory()->create();

        $this->withoutVite()->actingAs($user)->get('/scripts')
            ->assertOk()
            ->assertSee('Roteiros')
            ->assertSee('Nenhum roteiro criado', false);
    }

    public function test_create_manual(): void
    {
        $user = User::factory()->create();
        [$product, $blueprint, $persona, $avatar] = $this->context();

        $response = $this->actingAs($user)->post('/scripts', array_merge($this->manualData(), [
            'product_id' => $product->id,
            'content_blueprint_id' => $blueprint->id,
            'persona_id' => $persona->id,
            'avatar_id' => $avatar->id,
        ]));

        $script = ContentScript::firstOrFail();
        $response->assertRedirect(route('scripts.show', $script));
        $this->assertSame(ContentScriptStatus::Draft, $script->status);
        $this->assertSame(ContentScriptSource::Manual, $script->generation_source);
        $this->assertSame('en-US', $script->language);
    }

    public function test_source_manual_automatico(): void
    {
        $user = User::factory()->create();
        [$product, $blueprint, $persona, $avatar] = $this->context();

        $this->actingAs($user)->post('/scripts', array_merge($this->manualData(), [
            'product_id' => $product->id,
            'content_blueprint_id' => $blueprint->id,
            'persona_id' => $persona->id,
            'avatar_id' => $avatar->id,
            'generation_source' => 'ai',
        ]))->assertRedirect();

        $this->assertSame('manual', ContentScript::firstOrFail()->generation_source->value);
    }

    public function test_ai_generate_success(): void
    {
        $this->enableAi();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'id' => 'i-1', 'output_text' => json_encode($this->aiPayload()),
        ], 200)]);
        $user = User::factory()->create();
        [$product, $blueprint, $persona, $avatar] = $this->context();

        $response = $this->actingAs($user)->post(route('scripts.generate'), [
            'product_id' => $product->id,
            'content_blueprint_id' => $blueprint->id,
            'persona_id' => $persona->id,
            'avatar_id' => $avatar->id,
        ]);

        $script = ContentScript::firstOrFail();
        $response->assertRedirect(route('scripts.show', $script));
        $this->assertSame(ContentScriptStatus::Ready, $script->status);
        $this->assertSame(ContentScriptSource::Ai, $script->generation_source);
        $this->assertSame('Curiosity Beauty Discovery', $script->title);
        $this->assertDatabaseHas('ai_generations', ['operation' => 'content_script', 'status' => 'success']);
    }

    public function test_ai_generate_failed(): void
    {
        $this->enableAi();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'x'], 503)]);
        $user = User::factory()->create();
        [$product, $blueprint, $persona, $avatar] = $this->context();

        $this->actingAs($user)->post(route('scripts.generate'), [
            'product_id' => $product->id,
            'content_blueprint_id' => $blueprint->id,
            'persona_id' => $persona->id,
            'avatar_id' => $avatar->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('content_scripts', [
            'status' => ContentScriptStatus::Failed,
            'error_code' => 'service_unavailable',
        ]);
    }

    public function test_timeout_nao_quebra(): void
    {
        config()->set('ai.google.enabled', false);
        $user = User::factory()->create();
        [$product, $blueprint, $persona, $avatar] = $this->context();

        $this->actingAs($user)->post(route('scripts.generate'), [
            'product_id' => $product->id,
            'content_blueprint_id' => $blueprint->id,
            'persona_id' => $persona->id,
            'avatar_id' => $avatar->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('content_scripts', [
            'status' => ContentScriptStatus::Failed,
            'error_code' => 'provider_disabled',
        ]);
        $this->withoutVite()->actingAs($user)->get('/scripts')->assertOk();
    }

    public function test_invalid_schema_failed(): void
    {
        $this->enableAi();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'id' => 'x', 'output_text' => json_encode(['title' => 'Só título']),
        ], 200)]);
        $user = User::factory()->create();
        [$product, $blueprint, $persona, $avatar] = $this->context();

        $this->actingAs($user)->post(route('scripts.generate'), [
            'product_id' => $product->id,
            'content_blueprint_id' => $blueprint->id,
            'persona_id' => $persona->id,
            'avatar_id' => $avatar->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('content_scripts', [
            'status' => ContentScriptStatus::Failed,
            'error_code' => 'schema_mismatch',
        ]);
    }

    public function test_context_language_conflict(): void
    {
        $this->enableAi();
        $user = User::factory()->create();
        $product = Product::factory()->create(['language' => 'en-US', 'market' => 'US']);
        $blueprint = ContentBlueprint::factory()->create(['language' => 'en-US', 'market' => 'US']);
        $persona = Persona::factory()->create(['language' => 'pt-BR', 'market' => 'US']);
        $avatar = Avatar::factory()->create(['language' => 'en-US', 'market' => 'US']);

        $response = $this->actingAs($user)->post(route('scripts.generate'), [
            'product_id' => $product->id,
            'content_blueprint_id' => $blueprint->id,
            'persona_id' => $persona->id,
            'avatar_id' => $avatar->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('script_notice');
        $this->assertDatabaseCount('content_scripts', 0);
        Http::assertNothingSent();
    }

    public function test_context_market_conflict(): void
    {
        $this->enableAi();
        $user = User::factory()->create();
        $product = Product::factory()->create(['language' => 'en-US', 'market' => 'BR']);
        $blueprint = ContentBlueprint::factory()->create(['language' => 'en-US', 'market' => 'US']);
        $persona = Persona::factory()->create(['language' => 'en-US', 'market' => 'US']);
        $avatar = Avatar::factory()->create(['language' => 'en-US', 'market' => 'US']);

        $response = $this->actingAs($user)->post(route('scripts.generate'), [
            'product_id' => $product->id,
            'content_blueprint_id' => $blueprint->id,
            'persona_id' => $persona->id,
            'avatar_id' => $avatar->id,
        ]);

        $response->assertSessionHas('script_notice');
        $this->assertDatabaseCount('content_scripts', 0);
    }

    public function test_only_active_selectable(): void
    {
        $user = User::factory()->create();
        $archived = Product::factory()->create(['status' => 'archived']);
        [$product, $blueprint, $persona, $avatar] = $this->context();

        $response = $this->actingAs($user)->post(route('scripts.generate'), [
            'product_id' => $archived->id,
            'content_blueprint_id' => $blueprint->id,
            'persona_id' => $persona->id,
            'avatar_id' => $avatar->id,
        ]);

        $response->assertSessionHasErrors('product_id');
        $this->assertDatabaseCount('content_scripts', 0);
    }

    public function test_manual_draft_edit_e_mark_ready(): void
    {
        $user = User::factory()->create();
        [$product, $blueprint, $persona, $avatar] = $this->context();
        $script = ContentScript::factory()->create([
            'product_id' => $product->id,
            'content_blueprint_id' => $blueprint->id,
            'persona_id' => $persona->id,
            'avatar_id' => $avatar->id,
            'status' => ContentScriptStatus::Draft,
        ]);

        $this->actingAs($user)->put(
            route('scripts.update', $script),
            array_merge($this->manualData(), [
                'product_id' => $product->id,
                'content_blueprint_id' => $blueprint->id,
                'persona_id' => $persona->id,
                'avatar_id' => $avatar->id,
                'title' => 'Título editado',
            ])
        )->assertRedirect();

        $this->assertSame('Título editado', $script->fresh()->title);

        $this->actingAs($user)->post(route('scripts.ready', $script))->assertRedirect();

        $this->assertSame(ContentScriptStatus::Ready, $script->fresh()->status);
    }

    public function test_ready_edit_e_approve(): void
    {
        $user = User::factory()->create();
        $script = ContentScript::factory()->create(['status' => ContentScriptStatus::Ready]);

        $this->actingAs($user)->post(route('scripts.approve', $script))->assertRedirect();

        $script->refresh();
        $this->assertSame(ContentScriptStatus::Approved, $script->status);
        $this->assertNotNull($script->approved_at);
    }

    public function test_approved_nao_edita(): void
    {
        $user = User::factory()->create();
        [$product, $blueprint, $persona, $avatar] = $this->context();
        $script = ContentScript::factory()->create([
            'product_id' => $product->id,
            'content_blueprint_id' => $blueprint->id,
            'persona_id' => $persona->id,
            'avatar_id' => $avatar->id,
            'status' => ContentScriptStatus::Approved,
            'title' => 'Original',
        ]);

        $this->actingAs($user)->put(
            route('scripts.update', $script),
            array_merge($this->manualData(), [
                'product_id' => $product->id,
                'content_blueprint_id' => $blueprint->id,
                'persona_id' => $persona->id,
                'avatar_id' => $avatar->id,
                'title' => 'Alterado',
            ])
        )->assertStatus(409);

        $this->assertSame('Original', $script->fresh()->title);
    }

    public function test_failed_retry_preserva_historico(): void
    {
        $this->enableAi();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'x'], 503)]);
        $user = User::factory()->create();
        [$product, $blueprint, $persona, $avatar] = $this->context();
        $ctx = [
            'product_id' => $product->id,
            'content_blueprint_id' => $blueprint->id,
            'persona_id' => $persona->id,
            'avatar_id' => $avatar->id,
        ];

        $this->actingAs($user)->post(route('scripts.generate'), $ctx)->assertRedirect();
        $this->actingAs($user)->post(route('scripts.generate'), $ctx)->assertRedirect();

        $this->assertSame(2, ContentScript::count());
    }

    public function test_slug_unico(): void
    {
        $user = User::factory()->create();
        [$product, $blueprint, $persona, $avatar] = $this->context();

        $data = array_merge($this->manualData(['title' => 'Mesmo Título']), [
            'product_id' => $product->id,
            'content_blueprint_id' => $blueprint->id,
            'persona_id' => $persona->id,
            'avatar_id' => $avatar->id,
        ]);

        $this->actingAs($user)->post('/scripts', $data)->assertRedirect();
        $this->actingAs($user)->post('/scripts', $data)->assertRedirect();

        $this->assertDatabaseHas('content_scripts', ['slug' => 'mesmo-titulo-2']);
    }

    public function test_source_nao_adulteravel(): void
    {
        $user = User::factory()->create();
        [$product, $blueprint, $persona, $avatar] = $this->context();

        $this->actingAs($user)->post('/scripts', array_merge($this->manualData(), [
            'product_id' => $product->id,
            'content_blueprint_id' => $blueprint->id,
            'persona_id' => $persona->id,
            'avatar_id' => $avatar->id,
            'generation_source' => 'ai',
            'provider' => 'evil',
        ]))->assertRedirect();

        $script = ContentScript::firstOrFail();
        $this->assertSame('manual', $script->generation_source->value);
        $this->assertNull($script->provider);
    }

    public function test_archived_matrix(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        [$product, $blueprint, $persona, $avatar] = $this->context();
        $ctx = [
            'product_id' => $product->id,
            'content_blueprint_id' => $blueprint->id,
            'persona_id' => $persona->id,
            'avatar_id' => $avatar->id,
        ];
        $draft = ContentScript::factory()->create(array_merge($ctx, ['status' => ContentScriptStatus::Draft]));

        $this->actingAs($operator)->put(
            route('scripts.update', $draft),
            array_merge($this->manualData(), $ctx, ['status' => ContentScriptStatus::Archived->value])
        )->assertForbidden();

        $this->actingAs($operator)->put(
            route('scripts.update', $draft),
            array_merge($this->manualData(), $ctx, ['status' => ContentScriptStatus::Draft->value])
        )->assertRedirect();

        $this->actingAs($admin)->put(
            route('scripts.update', $draft),
            array_merge($this->manualData(), $ctx, ['status' => ContentScriptStatus::Archived->value])
        )->assertRedirect();

        $this->assertTrue($draft->fresh()->isArchived());
    }

    public function test_sidebar_ativa(): void
    {
        $user = User::factory()->create();

        $this->withoutVite()->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee(route('content.index'), false);
    }

    public function test_index_exibe_editar_conforme_editabilidade(): void
    {
        $user = User::factory()->create();

        foreach ([
            ContentScriptStatus::Draft->value => true,
            ContentScriptStatus::Ready->value => true,
            ContentScriptStatus::Approved->value => false,
            ContentScriptStatus::Failed->value => false,
            ContentScriptStatus::Generating->value => false,
            ContentScriptStatus::Archived->value => false,
        ] as $status => $canEdit) {
            ContentScript::query()->delete();

            $script = ContentScript::factory()->create(['status' => $status]);

            $response = $this->withoutVite()->actingAs($user)->get('/scripts')->assertOk();

            $response->assertSee(route('scripts.show', $script), false);

            if ($canEdit) {
                $response->assertSee(route('scripts.edit', $script), false);
            } else {
                $response->assertDontSee(route('scripts.edit', $script), false);
            }
        }
    }
}
