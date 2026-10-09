<?php

namespace Tests\Feature\AI;

use App\Enums\ContentProductionStatus;
use App\Enums\ContentScriptStatus;
use App\Enums\MediaAssetSource;
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
use App\Models\User;
use App\Models\VideoGenerationRequest;
use App\Services\AudioGenerationService;
use App\Services\AudioInspector;
use App\Services\AudioMetadata;
use App\Services\AudioVideoMergeException;
use App\Services\AudioVideoMergePlan;
use App\Services\AudioVideoMerger;
use App\Services\AudioVideoMergeService;
use App\Services\ContentProductionService;
use App\Services\ImageGenerationService;
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
 * Orquestrador de produção (Sprint 5.6.5): state machine, reuse-first,
 * continuação via relay, retry, idempotência, UI. Sem rede/FFmpeg real.
 */
class ContentProductionTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    private const VIDEO_FAKE = 'fake-video-binary';

    private const AUDIO_FAKE = 'fake-audio-binary';

    private function png600(): string
    {
        return substr_replace(
            (string) base64_decode(self::PNG_1X1),
            pack('N', 600).pack('N', 600),
            16,
            8
        );
    }

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
            $data = $request->data();
            $model = $data['model'] ?? '';

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

    private function imageAsset(ContentScript $script, bool $primary = true): MediaAsset
    {
        Storage::fake('public');
        $asset = MediaAsset::factory()->create(['mime_type' => 'image/png']);
        Storage::disk('public')->put($asset->path, $this->png600());
        $script->mediaAssets()->attach($asset->id, ['purpose' => 'scene', 'is_primary' => $primary]);

        return $asset;
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

    // ---- produção ----

    public function test_start_e_double_submit(): void
    {
        $user = User::factory()->create();
        $script = $this->context();
        $service = app(ContentProductionService::class);

        $first = $service->start($script, false, $user->id);
        $this->assertTrue($first['created']);

        $second = $service->start($script, false, $user->id);
        $this->assertFalse($second['created']);
        $this->assertSame($first['production']->id, $second['production']->id);
        $this->assertSame(1, ContentProduction::where('content_script_id', $script->id)->count());
    }

    public function test_full_happy_path(): void
    {
        Storage::fake('public');
        $this->enableAll();
        $this->fakeProviders();
        $this->fakeInspectors();
        $this->fakeMerger();
        $script = $this->context();

        $service = app(ContentProductionService::class);
        ['production' => $production] = $service->start($script, false, null);
        (new RunContentProductionJob($production->id))->handle($service);

        // Image criada e despachada; roda o Job filho (relay continua).
        $this->runChildJobs();

        $production = $production->fresh();
        $this->assertSame(ContentProductionStatus::Success, $production->status);
        $this->assertSame('completed', $production->current_step->value);

        $this->assertNotNull($production->image_media_asset_id);
        $this->assertNotNull($production->video_media_asset_id);
        $this->assertNotNull($production->audio_media_asset_id);
        $this->assertNotNull($production->final_media_asset_id);

        $final = MediaAsset::findOrFail($production->final_media_asset_id);
        $this->assertSame('video', $final->type->value);
        $this->assertSame('merged', $final->source->value);

        $this->assertSame(1, ImageGenerationRequest::count());
        $this->assertSame(1, VideoGenerationRequest::count());
        $this->assertSame(1, AudioGenerationRequest::count());
        $this->assertSame(1, AudioVideoMergeRequest::count());
    }

    public function test_reuse_image_pula_providers(): void
    {
        Storage::fake('public');
        $this->enableAll();
        $this->fakeProviders();
        $this->fakeInspectors();
        $this->fakeMerger();
        $script = $this->context();
        $this->imageAsset($script);

        $service = app(ContentProductionService::class);
        ['production' => $production] = $service->start($script, false, null);
        (new RunContentProductionJob($production->id))->handle($service);
        $this->runChildJobs();

        $this->assertSame(0, ImageGenerationRequest::count());
        $this->assertSame(ContentProductionStatus::Success, $production->fresh()->status);
    }

    public function test_reuse_video_audio_vai_direto_ao_merge(): void
    {
        Storage::fake('public');
        $this->enableAll();
        $this->fakeProviders();
        $this->fakeInspectors();
        $this->fakeMerger();
        $script = $this->context();
        $image = $this->imageAsset($script);

        $video = MediaAsset::factory()->create([
            'type' => MediaAssetType::Video, 'mime_type' => 'video/mp4',
            'width' => 720, 'height' => 1280, 'duration_seconds' => 10,
        ]);
        Storage::disk('public')->put($video->path, 'fake-video-bytes');
        VideoGenerationRequest::factory()->create([
            'content_script_id' => $script->id, 'status' => 'success', 'media_asset_id' => $video->id,
        ]);

        $audio = MediaAsset::factory()->create([
            'type' => MediaAssetType::Audio, 'mime_type' => 'audio/wav', 'duration_seconds' => 8,
        ]);
        Storage::disk('public')->put($audio->path, 'fake-audio-bytes');
        AudioGenerationRequest::factory()->create([
            'content_script_id' => $script->id, 'status' => 'success', 'media_asset_id' => $audio->id,
        ]);

        $service = app(ContentProductionService::class);
        ['production' => $production] = $service->start($script, false, null);
        (new RunContentProductionJob($production->id))->handle($service);
        $this->runChildJobs();

        $this->assertSame(0, ImageGenerationRequest::count());
        $this->assertSame(1, VideoGenerationRequest::count());
        $this->assertSame(1, AudioGenerationRequest::count());
        $this->assertSame(ContentProductionStatus::Success, $production->fresh()->status);
        $this->assertSame($image->id, $production->fresh()->image_media_asset_id);
    }

    public function test_final_existente_nao_gera_nada(): void
    {
        $script = $this->context();
        $final = MediaAsset::factory()->create([
            'type' => MediaAssetType::Video, 'source' => MediaAssetSource::Merged,
        ]);
        AudioVideoMergeRequest::factory()->create([
            'content_script_id' => $script->id, 'status' => 'success', 'output_media_asset_id' => $final->id,
        ]);

        $service = app(ContentProductionService::class);
        ['production' => $production] = $service->start($script, false, null);
        (new RunContentProductionJob($production->id))->handle($service);

        $production = $production->fresh();
        $this->assertSame(ContentProductionStatus::Success, $production->status);
        $this->assertSame($final->id, $production->final_media_asset_id);
        $this->assertSame(0, ImageGenerationRequest::count());
        Http::assertNothingSent();
    }

    public function test_force_new_regenera_video_e_merge(): void
    {
        Storage::fake('public');
        $this->enableAll();
        $this->fakeProviders();
        $this->fakeInspectors();
        $this->fakeMerger();
        $script = $this->context();
        $image = $this->imageAsset($script);
        $oldVideo = MediaAsset::factory()->create([
            'type' => MediaAssetType::Video, 'mime_type' => 'video/mp4',
            'width' => 720, 'height' => 1280, 'duration_seconds' => 10,
        ]);
        Storage::disk('public')->put($oldVideo->path, 'fake-video-bytes');
        VideoGenerationRequest::factory()->create([
            'content_script_id' => $script->id, 'status' => 'success', 'media_asset_id' => $oldVideo->id,
        ]);
        $oldAudio = MediaAsset::factory()->create([
            'type' => MediaAssetType::Audio, 'mime_type' => 'audio/wav', 'duration_seconds' => 8,
        ]);
        Storage::disk('public')->put($oldAudio->path, 'fake-audio-bytes');
        AudioGenerationRequest::factory()->create([
            'content_script_id' => $script->id, 'status' => 'success', 'media_asset_id' => $oldAudio->id,
        ]);
        $oldFinal = MediaAsset::factory()->create([
            'type' => MediaAssetType::Video, 'source' => MediaAssetSource::Merged,
        ]);
        AudioVideoMergeRequest::factory()->create([
            'content_script_id' => $script->id, 'status' => 'success',
            'video_media_asset_id' => $oldVideo->id, 'audio_media_asset_id' => $oldAudio->id,
            'output_media_asset_id' => $oldFinal->id,
        ]);

        $service = app(ContentProductionService::class);
        ['production' => $production] = $service->start($script, true, null);
        (new RunContentProductionJob($production->id))->handle($service);
        $this->runChildJobs();

        $production = $production->fresh();
        $this->assertSame(ContentProductionStatus::Success, $production->status);
        $this->assertSame($image->id, $production->image_media_asset_id);
        $this->assertNotSame($oldVideo->id, $production->video_media_asset_id);
        $this->assertSame($oldAudio->id, $production->audio_media_asset_id);
        $this->assertNotSame($oldFinal->id, $production->final_media_asset_id);
        $this->assertSame(0, ImageGenerationRequest::count());
        $this->assertSame(2, VideoGenerationRequest::count());
    }

    // ---- falhas e retry ----

    public function test_image_fail_para_tudo_e_retry_reusa(): void
    {
        $this->enableAll();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'x']], 503)]);
        $script = $this->context();

        $service = app(ContentProductionService::class);
        ['production' => $production] = $service->start($script, false, null);
        (new RunContentProductionJob($production->id))->handle($service);
        $this->runChildJobs();

        $production = $production->fresh();
        $this->assertSame(ContentProductionStatus::Failed, $production->status);
        $this->assertSame(0, VideoGenerationRequest::count());
        $this->assertSame(0, AudioGenerationRequest::count());
        $this->assertSame(0, AudioVideoMergeRequest::count());
    }

    public function test_video_fail_retry_reusa_imagem(): void
    {
        Storage::fake('public');
        $failVideo = true;
        $this->enableAll();
        $this->fakeInspectors();
        $this->fakeMerger();
        Http::fake(function ($request) use (&$failVideo) {
            $model = $request->data()['model'] ?? '';

            if (str_contains((string) $model, 'omni') && $failVideo) {
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

        // Retry: imagem reutilizada, vídeo tentado de novo.
        $failVideo = false;
        ['production' => $production] = $service->start($script, false, null);
        (new RunContentProductionJob($production->id))->handle($service);
        $this->runChildJobs();

        $this->assertSame(ContentProductionStatus::Success, $production->fresh()->status);
        $this->assertSame(1, ImageGenerationRequest::count());
        $this->assertSame(2, VideoGenerationRequest::count());
    }

    private function enableVideoOnlyFail(): void
    {
        $this->enableAll();
        Http::fake(function ($request) {
            $model = $request->data()['model'] ?? '';

            if (str_contains((string) $model, 'omni')) {
                return Http::response(['error' => ['message' => 'x']], 503);
            }

            return Http::response([
                'id' => 'i-1',
                'steps' => [
                    ['type' => 'model_output', 'content' => [['type' => 'image', 'data' => self::PNG_1X1, 'mime_type' => 'image/png']]],
                ],
            ], 200);
        });
    }

    public function test_merge_fail_retry_vai_direto_ao_merge(): void
    {
        Storage::fake('public');
        $this->enableAll();
        $this->fakeProviders();
        $this->fakeInspectors();
        $script = $this->context();
        $image = $this->imageAsset($script);

        $video = MediaAsset::factory()->create([
            'type' => MediaAssetType::Video, 'mime_type' => 'video/mp4',
            'width' => 720, 'height' => 1280, 'duration_seconds' => 10,
        ]);
        Storage::disk('public')->put($video->path, 'fake-video-bytes');
        VideoGenerationRequest::factory()->create([
            'content_script_id' => $script->id, 'status' => 'success', 'media_asset_id' => $video->id,
        ]);
        $audio = MediaAsset::factory()->create([
            'type' => MediaAssetType::Audio, 'mime_type' => 'audio/wav', 'duration_seconds' => 8,
        ]);
        Storage::disk('public')->put($audio->path, 'fake-audio-bytes');
        AudioGenerationRequest::factory()->create([
            'content_script_id' => $script->id, 'status' => 'success', 'media_asset_id' => $audio->id,
        ]);

        // Simula merge anterior falhado com snapshots já resolvidos.
        $service = app(ContentProductionService::class);
        ['production' => $production] = $service->start($script, false, null);
        $production->update([
            'video_media_asset_id' => $video->id,
            'audio_media_asset_id' => $audio->id,
            'image_media_asset_id' => $image->id,
        ]);

        // Força falha no merge (merger quebrado) e depois recupera.
        $this->app->bind(AudioVideoMerger::class, fn () => new class implements AudioVideoMerger
        {
            public function merge(AudioVideoMergePlan $plan, string $outputPath): void
            {
                throw new AudioVideoMergeException('ffmpeg_failed', 'x');
            }
        });
        (new RunContentProductionJob($production->id))->handle($service);
        $this->runChildJobs();
        $this->assertSame(ContentProductionStatus::Failed, $production->fresh()->status);

        $this->fakeMerger();
        ['production' => $production] = $service->start($script, false, null);
        (new RunContentProductionJob($production->id))->handle($service);
        $this->runChildJobs();

        $production = $production->fresh();
        $this->assertSame(ContentProductionStatus::Success, $production->status);
        $this->assertSame(2, AudioVideoMergeRequest::count());
    }

    // ---- guardas ----

    public function test_produce_post_e_elegibilidade(): void
    {
        $user = User::factory()->create();
        $script = $this->context();

        $this->post(route('content.produce', ['content' => $script]))->assertRedirect('/login');

        Queue::fake();
        $this->actingAs($user)->post(route('content.produce', ['content' => $script]))
            ->assertRedirect(route('content.show', ['content' => $script]));
        Queue::assertPushed(RunContentProductionJob::class);

        // Double submit: mesma produção ativa.
        $this->actingAs($user)->post(route('content.produce', ['content' => $script]))
            ->assertRedirect(route('content.show', ['content' => $script]));
        $this->assertSame(1, ContentProduction::count());

        $script->update(['status' => ContentScriptStatus::Draft]);
        $this->actingAs($user)->post(route('content.produce', ['content' => $script]))->assertForbidden();
    }

    public function test_motion_builder(): void
    {
        $script = $this->context();

        $motion = app(VideoMotionPromptBuilder::class)->build(
            $script, $script->product, $script->blueprint, $script->persona, $script->avatar,
        );

        $this->assertStringContainsString('Animate the starting image', $motion);
        $this->assertStringContainsString('Do not render text', $motion);
    }

    // ---- UI ----

    public function test_detail_processing_success_failed(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $script = $this->context();

        $failed = ContentProduction::factory()->create([
            'content_script_id' => $script->id,
            'status' => 'failed',
            'current_step' => 'audio',
            'error_code' => 'provider_failed',
        ]);
        $processing = ContentProduction::factory()->create([
            'content_script_id' => $script->id,
            'status' => 'processing',
            'current_step' => 'video',
        ]);

        $html = $this->withoutVite()->actingAs($user)->get(route('content.show', ['content' => $script]))
            ->assertOk()
            ->assertSee('Produzindo vídeo', false)
            ->assertSee('Criando vídeo', false)
            ->content();

        $processing->update(['status' => 'success', 'current_step' => 'completed']);
        $failed->delete();

        $final = MediaAsset::factory()->create([
            'type' => MediaAssetType::Video, 'source' => MediaAssetSource::Merged,
        ]);
        AudioVideoMergeRequest::factory()->create([
            'content_script_id' => $script->id, 'status' => 'success', 'output_media_asset_id' => $final->id,
        ]);
        $success = ContentProduction::factory()->create([
            'content_script_id' => $script->id,
            'status' => 'success',
            'current_step' => 'completed',
            'final_media_asset_id' => $final->id,
        ]);

        $this->withoutVite()->actingAs($user)->get(route('content.show', ['content' => $script]))
            ->assertOk()
            ->assertSee('Vídeo pronto', false)
            ->assertSee('Abrir vídeo', false)
            ->assertSee('Criar nova versão', false)
            ->assertDontSee('Produzindo vídeo', false);
    }
}
