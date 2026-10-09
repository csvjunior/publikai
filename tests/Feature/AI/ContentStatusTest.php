<?php

namespace Tests\Feature\AI;

use App\Enums\ContentScriptStatus;
use App\Enums\ContentType;
use App\Models\Avatar;
use App\Models\ContentBlueprint;
use App\Models\ContentProduction;
use App\Models\ContentScript;
use App\Models\Persona;
use App\Models\Product;
use App\Models\User;
use App\Services\ContentProductionService;
use App\Services\ContentStatusResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Microajuste 5.6.5: /content reflete o estado REAL via ContentProduction.
 * Sem Google/FFmpeg. Sem nova migration.
 */
class ContentStatusTest extends TestCase
{
    use RefreshDatabase;

    private function script(array $overrides = []): ContentScript
    {
        return ContentScript::factory()->create([
            'product_id' => Product::factory()->create(['language' => 'en-US', 'market' => 'US'])->id,
            'content_blueprint_id' => ContentBlueprint::factory()->create(['language' => 'en-US', 'market' => 'US'])->id,
            'persona_id' => Persona::factory()->create(['language' => 'en-US', 'market' => 'US'])->id,
            'avatar_id' => Avatar::factory()->create(['language' => 'en-US', 'market' => 'US'])->id,
            'content_type' => ContentType::Video,
            'status' => ContentScriptStatus::Approved,
            ...$overrides,
        ]);
    }

    private function production(ContentScript $script, string $status, string $step = 'video'): ContentProduction
    {
        return ContentProduction::factory()->create([
            'content_script_id' => $script->id,
            'status' => $status,
            'current_step' => $step,
        ]);
    }

    private function resolve(ContentScript $script): string
    {
        return app(ContentStatusResolver::class)->for($script->fresh(['productions']));
    }

    // A) sem production + pronto p/ revisar.

    public function test_a_no_production_ready_stays_review(): void
    {
        $script = $this->script(['status' => ContentScriptStatus::Ready]);

        $this->assertSame('Roteiro para revisar', $this->resolve($script));
    }

    // B) approved sem production.

    public function test_b_approved_no_production_ready_to_produce(): void
    {
        $script = $this->script(['status' => ContentScriptStatus::Approved]);

        $this->assertSame('Pronto para produzir', $this->resolve($script));
    }

    public function test_draft_stays_draft(): void
    {
        $script = $this->script(['status' => ContentScriptStatus::Draft]);

        $this->assertSame('Rascunho', $this->resolve($script));
    }

    /**
     * @dataProvider activeStates
     */
    #[DataProvider('activeStates')]
    public function test_c_to_f_active_productions_show_processing(string $status, string $step): void
    {
        $script = $this->script();
        $this->production($script, $status, $step);

        $this->assertSame('Processando', $this->resolve($script));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function activeStates(): array
    {
        return [
            'pending' => ['pending', 'preparing'],
            'processing image' => ['processing', 'image'],
            'processing video' => ['processing', 'video'],
            'processing audio' => ['processing', 'audio'],
        ];
    }

    // G) success → Pronto. H) failed → Falhou.

    public function test_g_success_shows_ready(): void
    {
        $script = $this->script();
        $this->production($script, 'success', 'completed');

        $this->assertSame('Pronto', $this->resolve($script));
    }

    public function test_h_failed_shows_failed(): void
    {
        $script = $this->script();
        $this->production($script, 'failed', 'video');

        $this->assertSame('Falhou', $this->resolve($script));
    }

    // I) success antiga + processing nova → Processando.

    public function test_i_old_success_new_processing(): void
    {
        $script = $this->script();
        $this->production($script, 'success', 'completed');
        $this->production($script, 'processing', 'video');

        $this->assertSame('Processando', $this->resolve($script));
    }

    // J) success antiga + failed nova → Falhou.

    public function test_j_old_success_new_failed(): void
    {
        $script = $this->script();
        $this->production($script, 'success', 'completed');
        $this->production($script, 'failed', 'video');

        $this->assertSame('Falhou', $this->resolve($script));
    }

    // K) failed antiga + success nova → Pronto.

    public function test_k_old_failed_new_success(): void
    {
        $script = $this->script();
        $this->production($script, 'failed', 'video');
        $this->production($script, 'success', 'completed');

        $this->assertSame('Pronto', $this->resolve($script));
    }

    // L) imagem sem production preservada.

    public function test_l_image_without_production_unchanged(): void
    {
        $script = $this->script(['content_type' => ContentType::Image, 'status' => ContentScriptStatus::Ready]);

        $this->assertSame('Roteiro para revisar', $this->resolve($script));
    }

    // Retry reaberta volta a Processando.

    public function test_retry_reopened_shows_processing(): void
    {
        $script = $this->script();
        $this->production($script, 'failed', 'video');

        $this->assertSame('Falhou', $this->resolve($script));

        ['production' => $retry] = app(ContentProductionService::class)->start($script, false, null);

        $this->assertSame('Processando', $this->resolve($script));
        $this->assertSame($retry->id, ContentProduction::where('content_script_id', $script->id)->latest('id')->first()->id);
    }

    // Consistência lista × detalhe.

    public function test_list_and_detail_agree_on_processing(): void
    {
        $user = User::factory()->create();
        $script = $this->script();
        $this->production($script, 'processing', 'video');

        $this->withoutVite()->actingAs($user)->get(route('content.index'))
            ->assertOk()
            ->assertSee('Processando', false);

        $this->withoutVite()->actingAs($user)->get(route('content.show', ['content' => $script]))
            ->assertOk()
            ->assertSee('Produzindo vídeo', false)
            ->assertSee('Criando vídeo', false);
    }

    // QA visual: badges distinguíveis lado a lado.

    public function test_index_lists_all_statuses(): void
    {
        $user = User::factory()->create();

        $review = $this->script(['status' => ContentScriptStatus::Ready]);
        $toProduce = $this->script(['status' => ContentScriptStatus::Approved]);
        $processing = $this->script();
        $this->production($processing, 'processing', 'video');
        $ready = $this->script();
        $this->production($ready, 'success', 'completed');
        $failed = $this->script();
        $this->production($failed, 'failed', 'video');

        $html = $this->withoutVite()->actingAs($user)->get(route('content.index'))
            ->assertOk()
            ->content();

        foreach (['Roteiro para revisar', 'Pronto para produzir', 'Processando', 'Pronto', 'Falhou'] as $badge) {
            $this->assertStringContainsString($badge, $html);
        }

        $this->assertStringNotContainsString('GenerateImageJob', $html);
        $this->assertStringNotContainsString('internal_error', $html);

        // Detail do approved continua com entry point de produção.
        $this->withoutVite()->actingAs($user)->get(route('content.show', ['content' => $toProduce]))
            ->assertOk()
            ->assertSee('Produzir vídeo', false);
    }

    // N+1: index não consulta productions por linha.

    public function test_index_eager_loads_productions(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 3) as $i) {
            $script = $this->script();
            $this->production($script, 'processing', 'video');
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->withoutVite()->actingAs($user)->get(route('content.index'));
        $response->assertOk();

        /** @var LengthAwarePaginator $scripts */
        $scripts = $response->viewData('scripts');

        foreach ($scripts as $script) {
            $this->assertTrue($script->relationLoaded('productions'));
        }

        $productionQueries = array_filter(
            DB::getQueryLog(),
            fn (array $q) => str_contains($q['query'], 'content_productions')
        );

        // 1 query de productions para a página inteira (eager), nunca por linha.
        $this->assertCount(1, $productionQueries);
    }
}
