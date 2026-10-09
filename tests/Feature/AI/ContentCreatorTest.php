<?php

namespace Tests\Feature\AI;

use App\Enums\ContentType;
use App\Models\Avatar;
use App\Models\ContentBlueprint;
use App\Models\ContentScript;
use App\Models\Persona;
use App\Models\Product;
use App\Models\User;
use App\Services\ContentCreatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Criador de conteúdo orientado a resultado (Sprint 5.6.4 refactor).
 * Sidebar simplificada, criar vídeo/imagem, lista, detalhe e revisão.
 */
class ContentCreatorTest extends TestCase
{
    use RefreshDatabase;

    private function aiPayload(): array
    {
        return [
            'title' => 'Curiosity Beauty Discovery',
            'hook' => 'Hook de teste.',
            'opening' => 'Abertura de teste.',
            'body' => 'Corpo de teste.',
            'cta' => 'CTA de teste.',
            'on_screen_text' => 'Teste',
            'visual_direction' => 'Close-ups.',
            'voice_direction' => 'Casual.',
            'duration_seconds' => 15,
        ];
    }

    private function enableAi(): void
    {
        config()->set('ai.google.enabled', true);
        config()->set('ai.google.auth_key', 'test-auth-key');
    }

    private function fakeScriptAi(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'id' => 'i-1', 'output_text' => json_encode($this->aiPayload()),
        ], 200)]);
    }

    private function entities(): array
    {
        $product = Product::factory()->create(['language' => 'en-US', 'market' => 'US']);
        $blueprint = ContentBlueprint::factory()->create(['language' => 'en-US', 'market' => 'US']);
        $persona = Persona::factory()->create(['language' => 'en-US', 'market' => 'US']);
        $avatar = Avatar::factory()->create(['language' => 'en-US', 'market' => 'US']);

        return [$product, $blueprint, $persona, $avatar];
    }

    private function validPost(array $overrides = []): array
    {
        [$product, , $persona, $avatar] = $this->entities();

        return array_merge([
            'type' => 'video',
            'product_id' => $product->id,
            'persona_id' => $persona->id,
            'avatar_id' => $avatar->id,
            'objective' => 'Mostrar o produto de forma natural e gerar curiosidade.',
        ], $overrides);
    }

    // ---- sidebar ----

    public function test_sidebar_simplificada(): void
    {
        $user = User::factory()->create();

        $html = $this->withoutVite()->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('Crie e acompanhe conteúdos para Instagram e TikTok em um só lugar.', false)
            ->assertDontSee('fase inicial', false)
            ->assertSee('Criar conteúdo', false)
            ->assertSee('Meus conteúdos', false)
            ->assertSee('Personas', false)
            ->assertSee('Avatares', false)
            ->assertSee('Produtos', false)
            ->assertSee('Referências', false)
            ->assertSee('Contas', false)
            ->assertSee('IA', false)
            ->content();

        foreach (['Roteiros', 'Blueprints', 'Ideias', 'Imagens', 'Vídeos'] as $legacy) {
            // Itens técnicos fora do menu (rotas continuam existindo).
            $this->assertStringNotContainsString('>'.$legacy.'<', $html);
        }

        $this->assertTrue(Route::has('scripts.index'));
        $this->assertTrue(Route::has('blueprints.index'));
    }

    // ---- criar ----

    public function test_create_mostra_tipos_e_formulario(): void
    {
        $user = User::factory()->create();
        $this->entities();

        $this->withoutVite()->actingAs($user)->get(route('content.create'))
            ->assertOk()
            ->assertSee('O que você quer criar?', false)
            ->assertSee('Criar vídeo', false)
            ->assertSee('Criar imagem', false);

        $this->withoutVite()->actingAs($user)->get(route('content.create', ['type' => 'video']))
            ->assertOk()
            ->assertSee('Objetivo do conteúdo', false)
            ->assertSee('Persona', false)
            ->assertSee('Avatar', false)
            ->assertSee('Configurações avançadas', false)
            ->assertSee('Gerar roteiro', false)
            ->assertSee('Selecionar produto', false)
            ->assertSee('Selecionar persona', false)
            ->assertSee('Selecionar avatar', false)
            ->assertSee('Revise o roteiro antes de produzir para evitar gerações desnecessárias.', false)
            ->assertDontSee('tone', false)
            ->assertDontSee('audience', false);
    }

    public function test_selects_exigem_escolha_e_preservam_old(): void
    {
        $user = User::factory()->create();
        [$product, , $persona, $avatar] = $this->entities();

        // Sem escolha: required falha (nada pré-selecionado).
        $this->actingAs($user)->post(route('content.store'), [
            'type' => 'video',
            'objective' => 'Objetivo válido com tamanho suficiente.',
        ])->assertSessionHasErrors(['product_id', 'persona_id', 'avatar_id']);

        // Escolha parcial com erro em outro campo: old() preserva na volta.
        $this->actingAs($user)
            ->from(route('content.create', ['type' => 'video']))
            ->followingRedirects()
            ->post(route('content.store'), [
                'type' => 'video',
                'product_id' => $product->id,
                'persona_id' => $persona->id,
                'avatar_id' => $avatar->id,
                'objective' => 'curto',
            ])
            ->assertSee('value="'.$product->id.'" selected', false)
            ->assertSee('value="'.$persona->id.'" selected', false)
            ->assertSee('value="'.$avatar->id.'" selected', false);
    }

    public function test_store_video_cria_roteiro_com_tipo(): void
    {
        $this->enableAi();
        $this->fakeScriptAi();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('content.store'), $this->validPost())
            ->assertRedirect();

        $script = ContentScript::firstOrFail();
        $this->assertSame(ContentType::Video, $script->content_type);
        $this->assertSame('Mostrar o produto de forma natural e gerar curiosidade.', $script->objective);
        $this->assertSame('Hook de teste.', $script->hook);
    }

    public function test_store_rejeita_entidades_arquivadas_ou_estrangeiras(): void
    {
        $this->enableAi();
        $this->fakeScriptAi();
        $user = User::factory()->create();
        [$product, , $persona, $avatar] = $this->entities();
        $product->update(['status' => 'archived']);

        $this->actingAs($user)->post(route('content.store'), $this->validPost([
            'product_id' => $product->id,
        ]))->assertNotFound();

        $this->actingAs($user)->post(route('content.store'), $this->validPost([
            'persona_id' => 999999,
        ]))->assertSessionHasErrors();

        $this->assertDatabaseCount('content_scripts', 0);
    }

    public function test_store_sem_provider_nao_quebra(): void
    {
        $this->enableAi();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'x'], 503)]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('content.store'), $this->validPost())
            ->assertRedirect();

        // Roteiro falhado ainda é um conteúdo revisável/visível.
        $this->assertSame('failed', ContentScript::firstOrFail()->status->value);
    }

    // ---- lista / detalhe / revisão ----

    public function test_index_agrega_com_status_amigavel(): void
    {
        $user = User::factory()->create();
        [$product, , $persona, $avatar] = $this->entities();
        ContentScript::factory()->create([
            'product_id' => $product->id,
            'persona_id' => $persona->id,
            'avatar_id' => $avatar->id,
            'content_blueprint_id' => ContentBlueprint::factory()->create()->id,
            'content_type' => ContentType::Video,
            'status' => 'ready',
        ]);

        $this->withoutVite()->actingAs($user)->get(route('content.index'))
            ->assertOk()
            ->assertSee('Meus conteúdos', false)
            ->assertSee('Vídeo', false)
            ->assertSee('Roteiro para revisar', false)
            ->assertSee($product->name, false)
            ->assertSee($persona->name, false)
            ->assertSee($avatar->name, false)
            ->assertDontSee('Blueprint', false);
    }

    public function test_show_review_checkpoint(): void
    {
        $user = User::factory()->create();
        [$product, $blueprint, $persona, $avatar] = $this->entities();
        $script = ContentScript::factory()->create([
            'product_id' => $product->id,
            'persona_id' => $persona->id,
            'avatar_id' => $avatar->id,
            'content_blueprint_id' => $blueprint->id,
            'content_type' => ContentType::Video,
            'status' => 'ready',
            'hook' => 'Hook visível.',
        ]);

        $this->withoutVite()->actingAs($user)->get(route('content.show', ['content' => $script]))
            ->assertOk()
            ->assertSee('Roteiro pronto para revisão', false)
            ->assertSee('Hook visível.', false)
            ->assertSee('Produzir vídeo', false)
            ->assertSee('Editar roteiro', false)
            ->assertSee('evitar gerações desnecessárias', false);
    }

    public function test_show_usa_fluxo_e_detalhes(): void
    {
        $user = User::factory()->create();
        [$product, $blueprint, $persona, $avatar] = $this->entities();
        $script = ContentScript::factory()->create([
            'product_id' => $product->id,
            'persona_id' => $persona->id,
            'avatar_id' => $avatar->id,
            'content_blueprint_id' => $blueprint->id,
            'content_type' => ContentType::Image,
            'status' => 'ready',
        ]);

        $this->withoutVite()->actingAs($user)->get(route('content.show', ['content' => $script]))
            ->assertOk()
            ->assertSee('Produção', false)
            ->assertSee('Detalhes', false)
            ->assertSee('Ver detalhes técnicos', false);
    }

    // ---- serviço ----

    public function test_creator_blueprint_default_e_tipo_legado(): void
    {
        [$product, $blueprint, $persona, $avatar] = $this->entities();
        $this->enableAi();
        $this->fakeScriptAi();

        $result = app(ContentCreatorService::class)->createVideo(
            $product, $persona, $avatar, 'Objetivo.', null, null, null,
        );

        $this->assertSame($blueprint->id, $result['blueprint']->id);
        $this->assertSame(ContentType::Video, $result['script']->content_type);

        // Linha antiga sem tipo: inferência (sem vídeo → Imagem).
        $legacy = ContentScript::factory()->create([
            'product_id' => $product->id,
            'persona_id' => $persona->id,
            'avatar_id' => $avatar->id,
            'content_blueprint_id' => $blueprint->id,
            'status' => 'ready',
        ]);
        $legacy->update(['content_type' => null]);

        $this->assertSame('Imagem', $legacy->contentTypeLabel());
        $this->assertSame('Roteiro para revisar', $legacy->contentStatusLabel());
    }
}
