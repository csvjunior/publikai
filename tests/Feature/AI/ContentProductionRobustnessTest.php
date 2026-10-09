<?php

namespace Tests\Feature\AI;

use App\Enums\ContentProductionStatus;
use App\Enums\ContentScriptStatus;
use App\Enums\MediaAssetType;
use App\Jobs\GenerateAudioJob;
use App\Jobs\GenerateImageJob;
use App\Jobs\GenerateVideoJob;
use App\Jobs\MergeAudioVideoJob;
use App\Jobs\RunContentProductionJob;
use App\Models\AudioGenerationRequest;
use App\Models\AudioVideoMergeRequest;
use App\Models\Avatar;
use App\Models\ContentBlueprint;
use App\Models\ContentProduction;
use App\Models\ContentScript;
use App\Models\ImageGenerationRequest;
use App\Models\MediaAsset;
use App\Models\Persona;
use App\Models\Product;
use App\Models\VideoGenerationRequest;
use App\Services\AudioGenerationService;
use App\Services\AudioInspector;
use App\Services\AudioMetadata;
use App\Services\AudioVideoMergePlan;
use App\Services\AudioVideoMerger;
use App\Services\AudioVideoMergeService;
use App\Services\ContentProductionService;
use App\Services\ImageGenerationService;
use App\Services\ProductionFlowService;
use App\Services\VideoGenerationService;
use App\Services\VideoInspector;
use App\Services\VideoMetadata;
use App\Services\VideoMotionPromptBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Auditoria de robustez assíncrona (Sprint 5.6.5): exception safety,
 * duplicate relay, out-of-order, stale jobs, failure between steps,
 * pending recovery, retry por etapa, force_new, continuação persistida.
 * Sem rede/FFmpeg real.
 */
class ContentProductionRobustnessTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    private const VIDEO_FAKE = 'fake-video-binary';

    private const AUDIO_FAKE = 'fake-audio-binary';

    private function enableAll(): void
    {
        config()->set('ai.google.enabled', true);
        config()->set('ai.google.auth_key', 'test-auth-key');
        config()->set('ai.google.image.enabled', true);
        config()->set('ai.google.video.enabled', true);
        config()->set('ai.google.audio.enabled', true);
    }

    private function fakeProviders(): void
    {
        Http::fake(function ($request) {
            $model = $request->data()['model'] ?? '';

            if (str_contains((string) $model, 'tts')) {
                return Http::response([
                    'id' => 'v1_audio1',
                    'steps' => [
                        ['type' => 'model_output', 'content' => [['type' => 'audio', 'data' => base64_encode(self::AUDIO_FAKE), 'mime_type' => 'audio/wav']]],
                    ],
                ], 200);
            }

            if (str_contains((string) $model, 'omni')) {
                return Http::response([
                    'id' => 'v1_video1',
                    'steps' => [
                        ['type' => 'model_output', 'content' => [['type' => 'video', 'data' => base64_encode(self::VIDEO_FAKE), 'mime_type' => 'video/mp4']]],
                    ],
                ], 200);
            }

            return Http::response([
                'id' => 'interaction-1',
                'steps' => [
                    ['type' => 'model_output', 'content' => [['type' => 'image', 'data' => self::PNG_1X1, 'mime_type' => 'image/png']]],
                ],
            ], 200);
        });
    }

    private function fakeInspectors(): void
    {
        $this->app->bind(VideoInspector::class, fn () => new class implements VideoInspector
        {
            public function inspect(string $path): ?VideoMetadata
            {
                return new VideoMetadata('video/mp4', 720, 1280, 8.0, 9999, true, 'h264', 'aac');
            }
        });
        $this->app->bind(AudioInspector::class, fn () => new class implements AudioInspector
        {
            public function inspect(string $path): ?AudioMetadata
            {
                return new AudioMetadata('audio/wav', 8.0, 24000, 1, 9999);
            }
        });
    }

    private function fakeMerger(): void
    {
        $this->app->bind(AudioVideoMerger::class, fn () => new class implements AudioVideoMerger
        {
            public function merge(AudioVideoMergePlan $plan, string $outputPath): void
            {
                if (! is_dir(dirname($outputPath))) {
                    mkdir(dirname($outputPath), 0755, true);
                }
                file_put_contents($outputPath, 'fake-merged');
            }
        });
    }

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

    private function runChildJobs(): void
    {
        foreach (ImageGenerationRequest::where('status', 'pending')->get() as $r) {
            (new GenerateImageJob($r->id))->handle(app(ImageGenerationService::class));
        }
        foreach (VideoGenerationRequest::where('status', 'pending')->get() as $r) {
            (new GenerateVideoJob($r->id))->handle(app(VideoGenerationService::class));
        }
        foreach (AudioGenerationRequest::where('status', 'pending')->get() as $r) {
            (new GenerateAudioJob($r->id))->handle(app(AudioGenerationService::class));
        }
        foreach (AudioVideoMergeRequest::where('status', 'pending')->get() as $r) {
            (new MergeAudioVideoJob($r->id))->handle(app(AudioVideoMergeService::class));
        }
    }

    // ---- 1+2. unexpected exception → failed, failed() idempotente ----

    public function test_unexpected_orchestrator_exception_fails_safely(): void
    {
        $script = $this->context();
        $this->app->bind(ProductionFlowService::class, fn () => new class extends ProductionFlowService
        {
            public function for(ContentScript $script): array
            {
                throw new \RuntimeException('boom');
            }
        });

        ['production' => $production] = app(ContentProductionService::class)->start($script, false, null);

        (new RunContentProductionJob($production->id))->handle(app(ContentProductionService::class));

        $production = $production->fresh();
        $this->assertSame(ContentProductionStatus::Failed, $production->status);
        $this->assertSame('internal_error', $production->error_code);
        $this->assertNotNull($production->completed_at);
        $this->assertSame(0, ImageGenerationRequest::count());
    }

    public function test_orchestrator_failed_is_idempotent(): void
    {
        $script = $this->context();
        $production = ContentProduction::factory()->create([
            'content_script_id' => $script->id,
            'status' => 'success',
            'current_step' => 'completed',
        ]);

        (new RunContentProductionJob($production->id))->failed(new \RuntimeException('hard timeout'));

        $this->assertSame('success', $production->fresh()->status->value);
    }

    // ---- 5. duplicate relay ----

    public function test_duplicate_child_relay_creates_single_next_step(): void
    {
        Storage::fake('public');
        $this->enableAll();
        $this->fakeProviders();
        $this->fakeInspectors();
        $this->fakeMerger();
        $script = $this->context();

        $service = app(ContentProductionService::class);
        ['production' => $production] = $service->start($script, false, null);

        Queue::fake();
        $service->advance($production->id);
        $this->assertSame(1, ImageGenerationRequest::count());
        $image = ImageGenerationRequest::first();

        // Executa a child diretamente (bypass da fila fake); o relay real
        // dentro do handle continua a produção. Chamadas extras devem ser
        // absorvidas sem duplicar a próxima etapa.
        (new GenerateImageJob($image->id))->handle(app(ImageGenerationService::class));

        $service->childFinished($production->id);
        $service->childFinished($production->id);

        $this->assertSame(1, VideoGenerationRequest::count());
    }

    // ---- 6. out-of-order: child ainda pending/processing ----

    public function test_rerun_while_child_pending_creates_nothing(): void
    {
        $script = $this->context();
        $service = app(ContentProductionService::class);
        ['production' => $production] = $service->start($script, false, null);

        Queue::fake();
        $service->advance($production->id);
        $this->assertSame(1, ImageGenerationRequest::count());

        $service->advance($production->id);
        $service->advance($production->id);

        $this->assertSame(1, ImageGenerationRequest::count());
        $this->assertSame(0, VideoGenerationRequest::count());
        $this->assertTrue(in_array($production->fresh()->status->value, ['pending', 'processing'], true));
    }

    // ---- 7. stale job ----

    public function test_stale_orchestrator_job_is_noop(): void
    {
        $script = $this->context();
        $service = app(ContentProductionService::class);
        ['production' => $production] = $service->start($script, false, null);
        $production->update(['status' => 'success', 'current_step' => 'completed', 'completed_at' => now()]);

        (new RunContentProductionJob($production->id))->handle($service);

        $this->assertSame(0, ImageGenerationRequest::count());
        $this->assertSame('success', $production->fresh()->status->value);
    }

    // ---- 8. failure between steps ----

    public function test_unexpected_between_steps_fails_with_snapshot(): void
    {
        Storage::fake('public');
        $this->enableAll();
        $this->fakeProviders();
        $this->fakeInspectors();
        $this->fakeMerger();
        $script = $this->context();
        $breakMotion = false;
        $this->app->bind(VideoMotionPromptBuilder::class, function () use (&$breakMotion) {
            return new class($breakMotion) extends VideoMotionPromptBuilder
            {
                public function __construct(private bool &$break) {}

                public function build(
                    ContentScript $script,
                    Product $product,
                    ContentBlueprint $blueprint,
                    Persona $persona,
                    Avatar $avatar,
                ): string {
                    if ($this->break) {
                        throw new \RuntimeException('between boom');
                    }

                    return parent::build($script, $product, $blueprint, $persona, $avatar);
                }
            };
        });

        $service = app(ContentProductionService::class);
        ['production' => $production] = $service->start($script, false, null);

        // Segura a image como pending para controlar os passos.
        Queue::fake();
        $service->advance($production->id);
        $image = ImageGenerationRequest::first();
        $this->assertNotNull($image);

        // Quebra o vídeo ANTES da image executar: o relay falha seguro.
        $breakMotion = true;
        (new GenerateImageJob($image->id))->handle(app(ImageGenerationService::class));

        $production = $production->fresh();
        $this->assertSame(ContentProductionStatus::Failed, $production->status);
        $this->assertSame('internal_error', $production->error_code);
        $this->assertNotNull($production->image_media_asset_id);
        $this->assertSame(0, VideoGenerationRequest::count());

        // Retry reusa a imagem e continua.
        $breakMotion = false;
        ['production' => $retry] = app(ContentProductionService::class)->start($script, false, null);
        (new RunContentProductionJob($retry->id))->handle(app(ContentProductionService::class));
        $this->runChildJobs();

        $this->assertSame(ContentProductionStatus::Success, $retry->fresh()->status);
        $this->assertSame(1, ImageGenerationRequest::count());
    }

    // ---- 9. pending recovery ----

    public function test_pending_child_is_redispatched_not_duplicated(): void
    {
        $script = $this->context();
        $service = app(ContentProductionService::class);
        ['production' => $production] = $service->start($script, false, null);

        Queue::fake();
        $service->advance($production->id);
        $this->assertSame(1, ImageGenerationRequest::count());

        // Reentrada: child ainda pending → recupera (re-dispatch) sem duplicar.
        $service->advance($production->id);
        $this->assertSame(1, ImageGenerationRequest::count());
        Queue::assertPushed(GenerateImageJob::class, 2);
    }

    // ---- 13. retry audio preserva anteriores ----

    public function test_audio_fail_retry_preserves_image_video(): void
    {
        Storage::fake('public');
        $failAudio = true;
        $this->enableAll();
        $this->fakeInspectors();
        $this->fakeMerger();
        Http::fake(function ($request) use (&$failAudio) {
            $model = $request->data()['model'] ?? '';

            if (str_contains((string) $model, 'tts') && $failAudio) {
                return Http::response(['error' => ['message' => 'x']], 503);
            }

            if (str_contains((string) $model, 'tts')) {
                return Http::response([
                    'id' => 'v1_audio1',
                    'steps' => [
                        ['type' => 'model_output', 'content' => [['type' => 'audio', 'data' => base64_encode(self::AUDIO_FAKE), 'mime_type' => 'audio/wav']]],
                    ],
                ], 200);
            }

            if (str_contains((string) $model, 'omni')) {
                return Http::response([
                    'id' => 'v1_video1',
                    'steps' => [
                        ['type' => 'model_output', 'content' => [['type' => 'video', 'data' => base64_encode(self::VIDEO_FAKE), 'mime_type' => 'video/mp4']]],
                    ],
                ], 200);
            }

            return Http::response([
                'id' => 'i-1',
                'steps' => [
                    ['type' => 'model_output', 'content' => [['type' => 'image', 'data' => self::PNG_1X1, 'mime_type' => 'image/png']]],
                ],
            ], 200);
        });
        $script = $this->context();

        $service = app(ContentProductionService::class);
        ['production' => $production] = $service->start($script, false, null);
        (new RunContentProductionJob($production->id))->handle($service);
        $this->runChildJobs();

        $this->assertSame(ContentProductionStatus::Failed, $production->fresh()->status);
        $this->assertSame(1, ImageGenerationRequest::count());
        $this->assertSame(1, VideoGenerationRequest::count());
        $this->assertSame(1, AudioGenerationRequest::count());

        $failAudio = false;
        ['production' => $production] = $service->start($script, false, null);
        (new RunContentProductionJob($production->id))->handle($service);
        $this->runChildJobs();

        $this->assertSame(ContentProductionStatus::Success, $production->fresh()->status);
        $this->assertSame(1, ImageGenerationRequest::count());
        $this->assertSame(1, VideoGenerationRequest::count());
        $this->assertSame(2, AudioGenerationRequest::count());
    }

    // ---- 14. force_new nunca reusa vídeo antigo ----

    public function test_force_new_ignores_old_video(): void
    {
        Storage::fake('public');
        $this->enableAll();
        $this->fakeProviders();
        $this->fakeInspectors();
        $this->fakeMerger();
        $script = $this->context();

        $image = MediaAsset::factory()->create(['mime_type' => 'image/png']);
        Storage::disk('public')->put($image->path, (string) base64_decode(self::PNG_1X1));
        $script->mediaAssets()->attach($image->id, ['purpose' => 'scene', 'is_primary' => true]);

        $oldVideo = MediaAsset::factory()->create([
            'type' => MediaAssetType::Video, 'mime_type' => 'video/mp4',
            'width' => 720, 'height' => 1280, 'duration_seconds' => 10,
        ]);
        Storage::disk('public')->put($oldVideo->path, 'fake-video-bytes');
        VideoGenerationRequest::factory()->create([
            'content_script_id' => $script->id, 'status' => 'success', 'media_asset_id' => $oldVideo->id,
        ]);

        $service = app(ContentProductionService::class);
        ['production' => $production] = $service->start($script, true, null);
        (new RunContentProductionJob($production->id))->handle($service);
        $this->runChildJobs();

        $production = $production->fresh();
        $this->assertTrue($production->status === ContentProductionStatus::Success);
        $this->assertSame($image->id, $production->image_media_asset_id);
        $this->assertNotSame($oldVideo->id, $production->video_media_asset_id);
    }

    // ---- 15. continuação com estado persistido (serviços frescos) ----

    public function test_persisted_state_continuation_across_fresh_services(): void
    {
        Storage::fake('public');
        $this->enableAll();
        $this->fakeProviders();
        $this->fakeInspectors();
        $this->fakeMerger();
        $script = $this->context();

        ['production' => $production] = app(ContentProductionService::class)->start($script, false, null);
        $id = $production->id;

        // Cada etapa resolve um service novo (nada em memória atravessa calls).
        (new RunContentProductionJob($id))->handle(app(ContentProductionService::class));

        foreach (ImageGenerationRequest::where('status', 'pending')->get() as $r) {
            (new GenerateImageJob($r->id))->handle(app(ImageGenerationService::class));
        }
        app(ContentProductionService::class)->childFinished($id);

        foreach (VideoGenerationRequest::where('status', 'pending')->get() as $r) {
            (new GenerateVideoJob($r->id))->handle(app(VideoGenerationService::class));
        }
        app(ContentProductionService::class)->childFinished($id);

        foreach (AudioGenerationRequest::where('status', 'pending')->get() as $r) {
            (new GenerateAudioJob($r->id))->handle(app(AudioGenerationService::class));
        }
        app(ContentProductionService::class)->childFinished($id);

        foreach (AudioVideoMergeRequest::where('status', 'pending')->get() as $r) {
            (new MergeAudioVideoJob($r->id))->handle(app(AudioVideoMergeService::class));
        }

        $production = ContentProduction::find($id);
        $this->assertSame(ContentProductionStatus::Success, $production->status);
        $this->assertNotNull($production->final_media_asset_id);
        $this->assertSame('merged', MediaAsset::find($production->final_media_asset_id)->source->value);
    }
}
