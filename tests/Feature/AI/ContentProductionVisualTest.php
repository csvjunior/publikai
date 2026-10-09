<?php

namespace Tests\Feature\AI;

use App\Enums\ContentScriptStatus;
use App\Enums\MediaAssetSource;
use App\Enums\MediaAssetType;
use App\Models\AudioVideoMergeRequest;
use App\Models\Avatar;
use App\Models\ContentBlueprint;
use App\Models\ContentProduction;
use App\Models\ContentScript;
use App\Models\MediaAsset;
use App\Models\Persona;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Revisão visual final (Sprint 5.6.5): estados A–H da detail sem provider
 * real. Garante copy amigável, CTA única, nenhum termo técnico exposto.
 */
class ContentProductionVisualTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var string[]
     */
    private const FORBIDDEN = [
        'GenerateImageJob',
        'GenerateVideoJob',
        'GenerateAudioJob',
        'MergeAudioVideoJob',
        'RunContentProductionJob',
        'ImageGenerationRequest',
        'VideoGenerationRequest',
        'AudioGenerationRequest',
        'AudioVideoMergeRequest',
        'MediaAsset',
        'Gemini',
        'FFmpeg',
        'FFprobe',
        'TTS',
        'Kore',
        'internal_error',
        'provider_failed',
        'ContentProduction',
        'Etapas internas',
    ];

    private function context(): ContentScript
    {
        return ContentScript::factory()->create([
            'product_id' => Product::factory()->create(['language' => 'en-US', 'market' => 'US'])->id,
            'content_blueprint_id' => ContentBlueprint::factory()->create(['language' => 'en-US', 'market' => 'US'])->id,
            'persona_id' => Persona::factory()->create(['language' => 'en-US', 'market' => 'US'])->id,
            'avatar_id' => Avatar::factory()->create(['language' => 'en-US', 'market' => 'US'])->id,
            'status' => ContentScriptStatus::Approved,
            'content_type' => 'video',
        ]);
    }

    private function page(ContentScript $script, User $user): string
    {
        return $this->withoutVite()->actingAs($user)
            ->get(route('content.show', ['content' => $script]))
            ->assertOk()
            ->content();
    }

    private function assertNoLeak(string $html): void
    {
        foreach (self::FORBIDDEN as $term) {
            $this->assertStringNotContainsString($term, $html, "Vazamento técnico: {$term}");
        }
    }

    private function production(array $overrides): ContentProduction
    {
        return ContentProduction::factory()->create($overrides);
    }

    // A) pronto para produzir.

    public function test_state_a_ready_to_produce(): void
    {
        $user = User::factory()->create();
        $script = $this->context();

        $html = $this->withoutVite()->actingAs($user)
            ->get(route('content.show', ['content' => $script]))
            ->assertOk()
            ->assertSee('Produzir vídeo', false)
            ->assertSee('Revise o texto antes de produzir', false)
            ->content();

        $this->assertSame(1, substr_count($html, 'Produzir vídeo'));
        $this->assertNoLeak($html);
    }

    // B) aguardando fila (pending).

    public function test_state_b_pending_shows_waiting(): void
    {
        $user = User::factory()->create();
        $script = $this->context();
        $this->production([
            'content_script_id' => $script->id,
            'status' => 'pending',
            'current_step' => 'preparing',
        ]);

        $html = $this->page($script, $user);

        $this->assertStringContainsString('Aguardando processamento', $html);
        $this->assertStringNotContainsString('Produzir vídeo', $html);
        $this->assertNoLeak($html);
    }

    /**
     * @return array<string, array{string, string}>
     */
    #[DataProvider('processingSteps')]
    public function test_states_c_to_f_current_step_focus(string $step, string $label): void
    {
        $user = User::factory()->create();
        $script = $this->context();
        $this->production([
            'content_script_id' => $script->id,
            'status' => 'processing',
            'current_step' => $step,
        ]);

        $html = $this->page($script, $user);

        $this->assertStringContainsString('Produzindo vídeo', $html);
        $this->assertStringContainsString($label, $html);
        $this->assertStringContainsString('font-medium text-ink', $html);
        $this->assertStringNotContainsString('Produzir vídeo', $html);
        $this->assertStringNotContainsString('75%', $html);
        $this->assertNoLeak($html);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function processingSteps(): array
    {
        return [
            'image' => ['image', 'Preparando visual'],
            'video' => ['video', 'Criando vídeo'],
            'audio' => ['audio', 'Criando narração'],
            'finalizing' => ['finalizing', 'Finalizando vídeo'],
        ];
    }

    // G) success com player.

    public function test_state_g_success_player_and_version(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $script = $this->context();

        $final = MediaAsset::factory()->create([
            'type' => MediaAssetType::Video,
            'source' => MediaAssetSource::Merged,
            'mime_type' => 'video/mp4',
            'duration_seconds' => 8,
        ]);
        Storage::disk('public')->put($final->path, 'fake-final');
        AudioVideoMergeRequest::factory()->create([
            'content_script_id' => $script->id,
            'status' => 'success',
            'output_media_asset_id' => $final->id,
        ]);
        $this->production([
            'content_script_id' => $script->id,
            'status' => 'success',
            'current_step' => 'completed',
            'final_media_asset_id' => $final->id,
        ]);

        $html = $this->page($script, $user);

        foreach (['Vídeo pronto', '<video', 'Abrir vídeo', 'Final', 'Criar nova versão', 'reutilizando o que for possível'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
        $this->assertStringNotContainsString('Produzir vídeo', $html);
        $this->assertStringNotContainsString('Produzindo vídeo', $html);
        $this->assertNoLeak($html);
    }

    // H) falha no vídeo + retry.

    public function test_state_h_failed_video_retry(): void
    {
        $user = User::factory()->create();
        $script = $this->context();
        $this->production([
            'content_script_id' => $script->id,
            'status' => 'failed',
            'current_step' => 'video',
            'error_code' => 'provider_failed',
        ]);

        $html = $this->page($script, $user);

        foreach (['Não foi possível concluir o vídeo', 'Falhou ao criar o vídeo', 'Tentar novamente', 'Continuaremos a partir da etapa necessária'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
        $this->assertStringNotContainsString('Produzir vídeo', $html);
        $this->assertNoLeak($html);
    }

    // Flash messages do produce (fila fake: sem execução real).

    public function test_produce_flash_messages(): void
    {
        $user = User::factory()->create();
        $script = $this->context();

        Queue::fake();

        $this->actingAs($user)->post(route('content.produce', ['content' => $script]))
            ->assertRedirect()
            ->assertSessionHas('status', 'Produção iniciada.');

        $this->actingAs($user)->post(route('content.produce', ['content' => $script]))
            ->assertRedirect()
            ->assertSessionHas('status', 'Seu vídeo já está sendo produzido.');

        $this->assertSame(1, ContentProduction::where('content_script_id', $script->id)->count());

        $production = ContentProduction::where('content_script_id', $script->id)->firstOrFail();
        $production->update(['status' => 'failed', 'error_code' => 'x', 'completed_at' => now()]);

        $this->actingAs($user)->post(route('content.produce', ['content' => $script]))
            ->assertRedirect()
            ->assertSessionHas('status', 'Continuando de onde parou.');

        $this->assertSame(1, ContentProduction::where('content_script_id', $script->id)->count());
    }

    // Mobile: classes responsivas presentes.

    public function test_mobile_responsive_classes(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $script = $this->context();
        $this->production([
            'content_script_id' => $script->id,
            'status' => 'processing',
            'current_step' => 'video',
        ]);

        $html = $this->page($script, $user);

        // Header empilha no mobile; steps em lista vertical; sem larguras fixas.
        $this->assertStringContainsString('flex flex-col gap-3 sm:flex-row', $html);
        $this->assertStringContainsString('min-w-0', $html);
        $this->assertStringNotContainsString('style="width:', $html);
    }
}
