<?php

namespace Tests\Feature\AI;

use App\Enums\AudioGenerationRequestStatus;
use App\Enums\AudioVideoMergeRequestStatus;
use App\Enums\ContentScriptStatus;
use App\Enums\ImageGenerationRequestStatus;
use App\Enums\MediaAssetSource;
use App\Enums\MediaAssetType;
use App\Enums\VideoCompositionRequestStatus;
use App\Enums\VideoGenerationRequestStatus;
use App\Models\AudioGenerationRequest;
use App\Models\AudioVideoMergeRequest;
use App\Models\Avatar;
use App\Models\ContentBlueprint;
use App\Models\ContentScript;
use App\Models\ImageGenerationRequest;
use App\Models\MediaAsset;
use App\Models\Persona;
use App\Models\Product;
use App\Models\User;
use App\Models\VideoCompositionRequest;
use App\Models\VideoGenerationRequest;
use App\Services\ProductionFlowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Jornada de produção do Script (Sprint 5.6.4): estados, ação recomendada
 * (UMA), recomendação de assets, montagem nunca automática, UI guiada.
 */
class ProductionFlowTest extends TestCase
{
    use RefreshDatabase;

    private function context(): ContentScript
    {
        return ContentScript::factory()->create([
            'product_id' => Product::factory()->create(['language' => 'en-US', 'market' => 'US'])->id,
            'content_blueprint_id' => ContentBlueprint::factory()->create(['language' => 'en-US', 'market' => 'US'])->id,
            'persona_id' => Persona::factory()->create(['language' => 'en-US', 'market' => 'US'])->id,
            'avatar_id' => Avatar::factory()->create(['language' => 'en-US', 'market' => 'US'])->id,
            'status' => ContentScriptStatus::Ready,
        ]);
    }

    private function image(ContentScript $script, bool $primary = false): MediaAsset
    {
        $asset = MediaAsset::factory()->create(['mime_type' => 'image/png']);
        $script->mediaAssets()->attach($asset->id, ['purpose' => 'scene', 'is_primary' => $primary]);

        return $asset;
    }

    private function videoOutput(ContentScript $script, array $overrides = []): MediaAsset
    {
        $asset = MediaAsset::factory()->create(array_merge([
            'type' => MediaAssetType::Video,
            'mime_type' => 'video/mp4',
            'width' => 720,
            'height' => 1280,
            'duration_seconds' => 10,
        ], $overrides));

        return $asset;
    }

    private function audioOutput(): MediaAsset
    {
        return MediaAsset::factory()->create([
            'type' => MediaAssetType::Audio,
            'mime_type' => 'audio/wav',
            'duration_seconds' => 8,
        ]);
    }

    private function flow(ContentScript $script): array
    {
        $script->load(['mediaAssets', 'imageRequests', 'videoRequests', 'compositions', 'audioRequests', 'merges']);

        return app(ProductionFlowService::class)->for($script);
    }

    // ---- estados ----

    public function test_vazio_recomenda_imagem(): void
    {
        $flow = $this->flow($this->context());

        $this->assertSame('empty', $flow['visual']['status']);
        $this->assertSame('empty', $flow['video']['status']);
        $this->assertSame('empty', $flow['narration']['status']);
        $this->assertSame('empty', $flow['final']['status']);
        $this->assertSame('generate_image', $flow['recommended_action']);
        $this->assertFalse($flow['blocked_by_processing']);
    }

    public function test_image_only_recomenda_video_com_primary(): void
    {
        $script = $this->context();
        $this->image($script);
        $primary = $this->image($script, true);

        $flow = $this->flow($script);

        $this->assertSame('ready', $flow['visual']['status']);
        $this->assertSame('create_video', $flow['recommended_action']);
        $this->assertSame($primary->id, $flow['recommended_image_id']);
    }

    public function test_video_sem_audio_recomenda_narracao(): void
    {
        $script = $this->context();
        $this->image($script, true);
        $video = $this->videoOutput($script);
        VideoGenerationRequest::factory()->create([
            'content_script_id' => $script->id,
            'status' => VideoGenerationRequestStatus::Success,
            'media_asset_id' => $video->id,
        ]);

        $flow = $this->flow($script);

        $this->assertSame('generate_audio', $flow['recommended_action']);
        $this->assertSame($video->id, $flow['recommended_video_id']);
    }

    public function test_video_mais_narracao_recomenda_finalizar(): void
    {
        $script = $this->context();
        $this->image($script, true);
        $video = $this->videoOutput($script);
        VideoGenerationRequest::factory()->create([
            'content_script_id' => $script->id,
            'status' => VideoGenerationRequestStatus::Success,
            'media_asset_id' => $video->id,
        ]);
        $audio = $this->audioOutput();
        AudioGenerationRequest::factory()->create([
            'content_script_id' => $script->id,
            'status' => AudioGenerationRequestStatus::Success,
            'media_asset_id' => $audio->id,
        ]);

        $flow = $this->flow($script);

        $this->assertSame('finalize_video', $flow['recommended_action']);
        $this->assertSame($audio->id, $flow['recommended_audio_id']);
    }

    public function test_merged_recomenda_final_e_montagem_nunca(): void
    {
        $script = $this->context();
        $this->image($script, true);
        $video = $this->videoOutput($script);
        VideoGenerationRequest::factory()->create([
            'content_script_id' => $script->id,
            'status' => VideoGenerationRequestStatus::Success,
            'media_asset_id' => $video->id,
        ]);
        $audio = $this->audioOutput();
        AudioGenerationRequest::factory()->create([
            'content_script_id' => $script->id,
            'status' => AudioGenerationRequestStatus::Success,
            'media_asset_id' => $audio->id,
        ]);
        $final = $this->videoOutput($script, ['source' => MediaAssetSource::Merged]);
        AudioVideoMergeRequest::factory()->create([
            'content_script_id' => $script->id,
            'status' => AudioVideoMergeRequestStatus::Success,
            'output_media_asset_id' => $final->id,
        ]);
        // Montagem disponível, mas nunca recomendada automaticamente.
        VideoCompositionRequest::factory()->create(['content_script_id' => $script->id]);

        $flow = $this->flow($script);

        $this->assertSame('final_ready', $flow['recommended_action']);
        $this->assertSame($final->id, $flow['final']['asset']->id);
    }

    public function test_processing_bloqueia_cta_e_failed_tem_retry(): void
    {
        $script = $this->context();
        ImageGenerationRequest::factory()->create([
            'content_script_id' => $script->id,
            'status' => ImageGenerationRequestStatus::Processing,
        ]);

        $flow = $this->flow($script);

        $this->assertSame('processing', $flow['visual']['status']);
        $this->assertTrue($flow['blocked_by_processing']);

        $script2 = $this->context();
        ImageGenerationRequest::factory()->create([
            'content_script_id' => $script2->id,
            'status' => ImageGenerationRequestStatus::Failed,
        ]);

        $flow2 = $this->flow($script2);

        $this->assertSame('failed', $flow2['visual']['status']);
        $this->assertNotNull($flow2['visual']['retry_url']);
    }

    public function test_prefere_composed_e_ignora_falhos(): void
    {
        $script = $this->context();
        $this->image($script, true);
        $plain = $this->videoOutput($script);
        VideoGenerationRequest::factory()->create([
            'content_script_id' => $script->id,
            'status' => VideoGenerationRequestStatus::Success,
            'media_asset_id' => $plain->id,
        ]);
        $composed = $this->videoOutput($script, ['source' => MediaAssetSource::Composed]);
        VideoCompositionRequest::factory()->create([
            'content_script_id' => $script->id,
            'status' => VideoCompositionRequestStatus::Success,
            'output_media_asset_id' => $composed->id,
        ]);
        VideoGenerationRequest::factory()->create([
            'content_script_id' => $script->id,
            'status' => VideoGenerationRequestStatus::Failed,
        ]);

        $flow = $this->flow($script);

        $this->assertSame('ready', $flow['video']['status']);
        $this->assertSame($composed->id, $flow['recommended_video_id']);
    }

    // ---- UI ----

    public function test_producao_uma_cta_e_montagem_opcional(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $script = $this->context();
        $this->image($script, true);

        $html = $this->withoutVite()->actingAs($user)->get(route('scripts.show', $script))
            ->assertOk()
            ->assertSee('Produção', false)
            ->assertSee('Montar vídeo', false)
            ->assertSee('opcional', false)
            ->assertSee('Materiais', false)
            ->assertSee('Direção de produção', false)
            ->assertDontSee('Composition', false)
            ->assertDontSee('Merged', false)
            ->assertDontSee('MediaAsset', false)
            ->content();

        // Uma única ação principal dentro da área Produção (antes de Materiais).
        $production = (string) strstr($html, '<h3 id="materiais"', true);
        $this->assertSame(1, substr_count($production, 'bg-accent text-white'));
    }

    public function test_final_mostra_badge_e_sem_cta_dominante(): void
    {
        $user = User::factory()->create();
        $script = $this->context();
        $this->image($script, true);
        $video = $this->videoOutput($script);
        VideoGenerationRequest::factory()->create([
            'content_script_id' => $script->id,
            'status' => VideoGenerationRequestStatus::Success,
            'media_asset_id' => $video->id,
        ]);
        $audio = $this->audioOutput();
        AudioGenerationRequest::factory()->create([
            'content_script_id' => $script->id,
            'status' => AudioGenerationRequestStatus::Success,
            'media_asset_id' => $audio->id,
        ]);
        $final = $this->videoOutput($script, ['source' => MediaAssetSource::Merged]);
        AudioVideoMergeRequest::factory()->create([
            'content_script_id' => $script->id,
            'status' => AudioVideoMergeRequestStatus::Success,
            'output_media_asset_id' => $final->id,
        ]);

        $this->withoutVite()->actingAs($user)->get(route('scripts.show', $script))
            ->assertOk()
            ->assertSee('Vídeo final pronto', false)
            ->assertSee('Final', false)
            ->assertSee('Criar nova versão', false)
            ->assertDontSee('Finalizar vídeo', false);
    }
}
