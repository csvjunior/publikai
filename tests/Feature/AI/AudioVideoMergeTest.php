<?php

namespace Tests\Feature\AI;

use App\Enums\AudioVideoMergeRequestStatus;
use App\Enums\ContentScriptStatus;
use App\Enums\MediaAssetSource;
use App\Enums\MediaAssetType;
use App\Jobs\MergeAudioVideoJob;
use App\Models\AudioVideoMergeRequest;
use App\Models\Avatar;
use App\Models\ContentBlueprint;
use App\Models\ContentScript;
use App\Models\MediaAsset;
use App\Models\Persona;
use App\Models\Product;
use App\Models\User;
use App\Services\AudioInspector;
use App\Services\AudioMetadata;
use App\Services\AudioVideoMergeException;
use App\Services\AudioVideoMergePlan;
use App\Services\AudioVideoMerger;
use App\Services\AudioVideoMergeService;
use App\Services\FfmpegAudioVideoMerger;
use App\Services\VideoInspector;
use App\Services\VideoMetadata;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * Merge local vídeo + narração (Sprint 5.6.3, FFmpeg).
 * Domínio, policy, plano/args, Job, storage, UI/auth, segurança.
 * FFmpeg real nunca executa na suíte (FakeAudioVideoMerger).
 */
class AudioVideoMergeTest extends TestCase
{
    use RefreshDatabase;

    /** @var AudioVideoMergePlan|null */
    public $capturedPlan = null;

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

    private function videoAsset(int $duration = 10, array $overrides = []): MediaAsset
    {
        $asset = MediaAsset::factory()->create(array_merge([
            'type' => MediaAssetType::Video,
            'mime_type' => 'video/mp4',
            'width' => 720,
            'height' => 1280,
            'duration_seconds' => $duration,
        ], $overrides));
        Storage::disk('public')->put($asset->path, 'fake-video-bytes');

        return $asset;
    }

    private function audioAsset(): MediaAsset
    {
        $asset = MediaAsset::factory()->create([
            'type' => MediaAssetType::Audio,
            'mime_type' => 'audio/wav',
        ]);
        Storage::disk('public')->put($asset->path, 'fake-audio-bytes');

        return $asset;
    }

    private function link(ContentScript $script, MediaAsset $asset): void
    {
        $script->mediaAssets()->attach($asset->id, ['purpose' => 'scene', 'is_primary' => false]);
    }

    private function fakeInspector(?VideoMetadata $video = null): void
    {
        $video ??= new VideoMetadata('video/mp4', 720, 1280, 10.0, 9999, true, 'h264', 'aac');

        $this->app->bind(VideoInspector::class, fn () => new class($video) implements VideoInspector
        {
            public function __construct(private VideoMetadata $metadata) {}

            public function inspect(string $path): ?VideoMetadata
            {
                return $this->metadata;
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

    private function fakeMerger(bool $succeed = true): void
    {
        $test = $this;

        $this->app->bind(AudioVideoMerger::class, fn () => new class($test, $succeed) implements AudioVideoMerger
        {
            public function __construct(private $test, private bool $succeed) {}

            public function merge(AudioVideoMergePlan $plan, string $outputPath): void
            {
                $this->test->capturedPlan = $plan;

                if (! $this->succeed) {
                    throw new AudioVideoMergeException('ffmpeg_failed', 'Falha ao combinar vídeo e narração.');
                }

                if (! is_dir(dirname($outputPath))) {
                    mkdir(dirname($outputPath), 0755, true);
                }

                file_put_contents($outputPath, 'fake-merged-mp4');
            }
        });
    }

    // ---- domínio ----

    public function test_create_snapshot_e_mesmo_script(): void
    {
        Storage::fake('public');
        $script = $this->context();
        $video = $this->videoAsset();
        $audio = $this->audioAsset();
        $this->link($script, $video);
        $this->link($script, $audio);

        $request = app(AudioVideoMergeService::class)->createRequest($script->id, $video->id, $audio->id);

        $this->assertSame($video->id, $request->video_media_asset_id);
        $this->assertSame($audio->id, $request->audio_media_asset_id);
        $this->assertSame('video_master', $request->duration_policy->value);
    }

    public function test_create_rejeita(): void
    {
        Storage::fake('public');
        $script = $this->context();
        $video = $this->videoAsset();
        $audio = $this->audioAsset();
        $this->link($script, $video);
        $this->link($script, $audio);
        $foreign = $this->videoAsset();
        $merged = $this->videoAsset(10, ['source' => MediaAssetSource::Merged]);
        $this->link($script, $merged);
        $service = app(AudioVideoMergeService::class);

        // Asset de outro script.
        $this->expectException(NotFoundHttpException::class);
        $service->createRequest($script->id, $foreign->id, $audio->id);
    }

    public function test_create_rejeita_merged_e_tipo_errado(): void
    {
        Storage::fake('public');
        $script = $this->context();
        $merged = $this->videoAsset(10, ['source' => MediaAssetSource::Merged]);
        $this->link($script, $merged);
        $audio = $this->audioAsset();
        $this->link($script, $audio);
        $image = MediaAsset::factory()->create(['mime_type' => 'image/png']);
        $this->link($script, $image);

        try {
            app(AudioVideoMergeService::class)->createRequest($script->id, $merged->id, $audio->id);
            $this->fail('merged deveria ser bloqueado');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $this->expectException(HttpException::class);
        app(AudioVideoMergeService::class)->createRequest($script->id, $image->id, $audio->id);
    }

    // ---- policy / plano / args ----

    public function test_output_dura_o_video(): void
    {
        Storage::fake('public');
        $script = $this->context();
        $video = $this->videoAsset(10);
        $audio = $this->audioAsset();
        $this->link($script, $video);
        $this->link($script, $audio);
        $this->fakeMerger();
        $this->fakeInspector();

        $request = app(AudioVideoMergeService::class)->createRequest($script->id, $video->id, $audio->id);
        (new MergeAudioVideoJob($request->id))->handle(app(AudioVideoMergeService::class));

        // Áudio fake tem 8s; output deve durar o vídeo (10s).
        $this->assertSame(10.0, $this->capturedPlan->videoDurationSeconds);
        $output = MediaAsset::findOrFail($request->fresh()->output_media_asset_id);
        $this->assertSame(10, $output->duration_seconds);
    }

    public function test_merge_arguments(): void
    {
        $merger = new FfmpegAudioVideoMerger;
        $args = $merger->mergeArguments(
            new AudioVideoMergePlan('/tmp/v.mp4', '/tmp/a.wav', 10.0, 720, 1280, 30),
            '/tmp/out.mp4'
        );
        $flat = implode(' ', $args);

        foreach (['-map', '0:v:0', '1:a:0', 'libx264', 'yuv420p', 'aac', '128k', '48000', '-t', 'scale=720:1280', 'fps=30', '.mp4'] as $needle) {
            $this->assertStringContainsString($needle, $flat);
        }

        $this->assertStringNotContainsString(';', $flat);
        $this->assertStringNotContainsString('&&', $flat);
        $this->assertStringNotContainsString('$(', $flat);
    }

    // ---- job / storage / output ----

    public function test_job_success_e_storage(): void
    {
        Storage::fake('public');
        $script = $this->context();
        $video = $this->videoAsset();
        $audio = $this->audioAsset();
        $this->link($script, $video);
        $this->link($script, $audio);
        $this->fakeMerger();
        $this->fakeInspector();

        $request = app(AudioVideoMergeService::class)->createRequest($script->id, $video->id, $audio->id);
        (new MergeAudioVideoJob($request->id))->handle(app(AudioVideoMergeService::class));

        $request = $request->fresh();
        $this->assertSame(AudioVideoMergeRequestStatus::Success, $request->status);
        $this->assertNotNull($request->started_at);

        $output = MediaAsset::findOrFail($request->output_media_asset_id);
        $this->assertSame('video', $output->type->value);
        $this->assertSame('merged', $output->source->value);
        $this->assertStringStartsWith('videos/merged/', $output->path);
        Storage::disk('public')->assertExists($output->path);

        // Provenance: request guarda os 3 ids (sem parent único).
        $this->assertSame($video->id, $request->video_media_asset_id);
        $this->assertSame($audio->id, $request->audio_media_asset_id);
        $this->assertNull($output->parent_media_asset_id);
        $this->assertSame([], glob((string) storage_path('app/tmp/audio-video-merge/*')) ?: []);
    }

    public function test_job_failure_idempotencia_e_hard_failed(): void
    {
        Storage::fake('public');
        $script = $this->context();
        $video = $this->videoAsset();
        $audio = $this->audioAsset();
        $this->link($script, $video);
        $this->link($script, $audio);
        $this->fakeMerger(false);
        $this->fakeInspector();

        $request = app(AudioVideoMergeService::class)->createRequest($script->id, $video->id, $audio->id);
        (new MergeAudioVideoJob($request->id))->handle(app(AudioVideoMergeService::class));

        $this->assertSame('ffmpeg_failed', $request->fresh()->error_code);
        $this->assertNull($request->fresh()->output_media_asset_id);

        (new MergeAudioVideoJob($request->id))->handle(app(AudioVideoMergeService::class));
        $this->assertSame(0, MediaAsset::where('source', 'merged')->count());

        $pending = AudioVideoMergeRequest::factory()->create(['status' => AudioVideoMergeRequestStatus::Processing]);
        (new MergeAudioVideoJob($pending->id))->failed();
        $this->assertSame('timeout', $pending->fresh()->error_code);
    }

    public function test_output_sem_faixa_audio_rejeitado(): void
    {
        Storage::fake('public');
        $script = $this->context();
        $video = $this->videoAsset();
        $audio = $this->audioAsset();
        $this->link($script, $video);
        $this->link($script, $audio);
        $this->fakeMerger();
        $this->app->bind(VideoInspector::class, fn () => new class implements VideoInspector
        {
            public function inspect(string $path): ?VideoMetadata
            {
                return new VideoMetadata('video/mp4', 720, 1280, 10.0, 9999, false, 'h264', null);
            }
        });
        $this->fakeAudioInspector();

        $request = app(AudioVideoMergeService::class)->createRequest($script->id, $video->id, $audio->id);
        (new MergeAudioVideoJob($request->id))->handle(app(AudioVideoMergeService::class));

        $this->assertSame('invalid_output', $request->fresh()->error_code);
    }

    private function fakeAudioInspector(): void
    {
        $this->app->bind(AudioInspector::class, fn () => new class implements AudioInspector
        {
            public function inspect(string $path): ?AudioMetadata
            {
                return new AudioMetadata('audio/wav', 8.0, 24000, 1, 9999);
            }
        });
    }

    // ---- UI/auth/segurança ----

    public function test_merge_ui_auth(): void
    {
        $script = $this->context();

        $this->get(route('scripts.merges.create', $script))->assertRedirect('/login');
        $this->post(route('scripts.merges.store', $script))->assertRedirect('/login');

        $user = User::factory()->create();
        $foreign = $this->videoAsset();
        $this->actingAs($user)->post(route('scripts.merges.store', $script), [
            'video_media_asset_id' => $foreign->id,
            'audio_media_asset_id' => $this->audioAsset()->id,
        ])->assertNotFound();

        $script->update(['status' => ContentScriptStatus::Draft]);
        $this->actingAs($user)->get(route('scripts.merges.create', $script))->assertForbidden();
    }

    public function test_merge_post_e_show(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $script = $this->context();
        $video = $this->videoAsset();
        $audio = $this->audioAsset();
        $this->link($script, $video);
        $this->link($script, $audio);

        $this->withoutVite()->actingAs($user)->get(route('scripts.merges.create', $script))
            ->assertOk()
            ->assertSee('Adicionar narração ao vídeo', false)
            ->assertSee('Resultado', false);

        Queue::fake();
        $this->actingAs($user)->post(route('scripts.merges.store', $script), [
            'video_media_asset_id' => $video->id,
            'audio_media_asset_id' => $audio->id,
        ])->assertRedirect(route('scripts.show', $script));

        $this->assertDatabaseCount('audio_video_merge_requests', 1);
        Queue::assertPushed(MergeAudioVideoJob::class);

        $this->withoutVite()->actingAs($user)->get(route('scripts.show', $script))
            ->assertOk()
            ->assertSee('Com narração', false);
    }
}
