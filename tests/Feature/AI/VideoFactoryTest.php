<?php

namespace Tests\Feature\AI;

use App\AI\Contracts\AiVideoProvider;
use App\AI\Exceptions\AiProviderException;
use App\Enums\ContentScriptStatus;
use App\Enums\MediaAssetType;
use App\Enums\UserRole;
use App\Enums\VideoGenerationRequestStatus;
use App\Jobs\GenerateVideoJob;
use App\Models\AiGeneration;
use App\Models\Avatar;
use App\Models\ContentBlueprint;
use App\Models\ContentScript;
use App\Models\MediaAsset;
use App\Models\Persona;
use App\Models\Product;
use App\Models\User;
use App\Models\VideoGenerationRequest;
use App\Services\VideoGenerationService;
use App\Services\VideoInspector;
use App\Services\VideoMetadata;
use App\Services\VideoPromptBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Fundação de vídeo image-to-video (Sprint 5.6.0, Omni Flash síncrono):
 * provider REST interactions, source, output, Job, contextual, admin,
 * segurança. Sem polling/operação (Job/queue dão o async).
 */
class VideoFactoryTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    private const MP4_FAKE = 'fake-mp4-binary-content-for-tests';

    private function png600(): string
    {
        return substr_replace(
            (string) base64_decode(self::PNG_1X1),
            pack('N', 600).pack('N', 600),
            16,
            8
        );
    }

    private function enableVideo(): void
    {
        config()->set('ai.google.video.enabled', true);
        config()->set('ai.google.auth_key', 'test-auth-key');
    }

    private function fakeInspector(bool $valid = true, ?float $duration = 8.0): void
    {
        $metadata = $valid ? new VideoMetadata('video/mp4', 720, 1280, $duration, 12345) : null;

        $this->app->bind(VideoInspector::class, fn () => new class($metadata) implements VideoInspector
        {
            public function __construct(private ?VideoMetadata $metadata) {}

            public function inspect(string $path): ?VideoMetadata
            {
                return $this->metadata;
            }
        });
    }

    private function videoBlock(string $data, string $mime = 'video/mp4'): array
    {
        return [
            'id' => 'v1_test123',
            'steps' => [
                ['type' => 'model_output', 'content' => [['type' => 'video', 'data' => $data, 'mime_type' => $mime]]],
            ],
        ];
    }

    private function fakeOmni(array $json, int $status = 200): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($json, $status)]);
    }

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

    private function sourceAsset(?ContentScript $script = null): MediaAsset
    {
        $asset = MediaAsset::factory()->create(['mime_type' => 'image/png']);
        Storage::disk('public')->put($asset->path, $this->png600());

        if ($script) {
            $script->mediaAssets()->attach($asset->id, ['purpose' => 'scene', 'is_primary' => false]);
        }

        return $asset;
    }

    private function validPost(array $overrides = []): array
    {
        return array_merge([
            'motion' => 'Make the character walk slowly toward the camera.',
            'source_media_asset_id' => 0,
        ], $overrides);
    }

    // ---- provider ----

    public function test_provider_payload_oficial(): void
    {
        $this->enableVideo();
        $this->fakeOmni($this->videoBlock(base64_encode(self::MP4_FAKE)));

        $result = app(AiVideoProvider::class)
            ->generate('A cat walks.', $this->png600(), 'image/png', ['aspect_ratio' => '9:16']);

        $this->assertSame(self::MP4_FAKE, $result->videoData);
        $this->assertSame('video/mp4', $result->mimeType);
        $this->assertSame('v1_test123', $result->externalRequestId);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return str_contains($request->url(), '/interactions')
                && ! str_contains($request->url(), 'predictLongRunning')
                && ($data['model'] ?? null) === 'gemini-omni-1.1-flash'
                && $request->hasHeader('x-goog-api-key', 'test-auth-key')
                && ($data['input'][0]['type'] ?? null) === 'image'
                && base64_decode($data['input'][0]['data'] ?? '', true) === $this->png600()
                && ($data['input'][0]['mime_type'] ?? null) === 'image/png'
                && ($data['input'][1]['type'] ?? null) === 'text'
                && ($data['generation_config']['video_config']['task'] ?? null) === 'image_to_video'
                && ($data['response_format']['type'] ?? null) === 'video'
                && ($data['response_format']['aspect_ratio'] ?? null) === '9:16';
        });
    }

    public function test_provider_sem_bloco_video_e_oversize(): void
    {
        $this->enableVideo();
        $provider = app(AiVideoProvider::class);

        $this->fakeOmni(['id' => 'v1_x', 'steps' => []]);

        try {
            $provider->generate('A cat walks.', $this->png600(), 'image/png');
            $this->fail('deveria lançar');
        } catch (AiProviderException $e) {
            $this->assertSame('invalid_video', $e->errorCode);
        }

        config()->set('ai.google.video.max_download_bytes', 10);
        $this->fakeOmni($this->videoBlock(base64_encode(str_repeat('x', 100))));

        try {
            $provider->generate('A cat walks.', $this->png600(), 'image/png');
            $this->fail('deveria lançar');
        } catch (AiProviderException $e) {
            $this->assertSame('invalid_video', $e->errorCode);
        }
    }

    public function test_provider_invalid_request_400_e_rate_limit(): void
    {
        $this->enableVideo();
        $errors = [
            ['json' => ['error' => ['message' => 'Bad.', 'code' => 400, 'status' => 'INVALID_ARGUMENT']], 'status' => 400],
            ['json' => ['error' => ['message' => 'Slow.']], 'status' => 429],
        ];
        Http::fake(function () use (&$errors) {
            $next = array_shift($errors);

            return Http::response($next['json'], $next['status']);
        });
        $provider = app(AiVideoProvider::class);

        try {
            $provider->generate('A cat walks.', $this->png600(), 'image/png');
            $this->fail('deveria lançar');
        } catch (AiProviderException $e) {
            $this->assertSame('invalid_request', $e->errorCode);
        }

        try {
            $provider->generate('A cat walks.', $this->png600(), 'image/png');
            $this->fail('deveria lançar');
        } catch (AiProviderException $e) {
            $this->assertSame('rate_limited', $e->errorCode);
        }
    }

    // ---- service / job ----

    public function test_process_success_completo(): void
    {
        Storage::fake('public');
        $this->enableVideo();
        $this->fakeOmni($this->videoBlock(base64_encode(self::MP4_FAKE)));
        $this->fakeInspector();
        $script = $this->context();
        $source = $this->sourceAsset($script);

        $request = app(VideoGenerationService::class)->createRequest(
            'MOTION - A cat walks slowly toward the camera.',
            ['content_script_id' => $script->id, 'source_media_asset_id' => $source->id],
            null,
        );

        (new GenerateVideoJob($request->id))->handle(app(VideoGenerationService::class));

        $request = $request->fresh();
        $this->assertSame(VideoGenerationRequestStatus::Success, $request->status);
        $this->assertNotNull($request->started_at);
        $this->assertNotNull($request->completed_at);
        $this->assertTrue($request->started_at->lessThanOrEqualTo($request->completed_at));

        $output = MediaAsset::where('parent_media_asset_id', $source->id)->firstOrFail();
        $this->assertSame('video', $output->type->value);
        $this->assertSame('ai_generated', $output->source->value);
        $this->assertSame('video/mp4', $output->mime_type);
        $this->assertSame(8, $output->duration_seconds);
        $this->assertStringStartsWith('videos/', $output->path);
        Storage::disk('public')->assertExists($output->path);

        $this->assertDatabaseHas('media_assets', ['id' => $source->id]);
        $this->assertTrue($script->fresh()->videoRequests()->whereKey($request->id)->exists());

        $log = AiGeneration::firstWhere('operation', 'video_generation');
        $this->assertSame('success', $log->status->value);
        $this->assertSame($source->id, $log->metadata['source_media_asset_id']);
        $this->assertSame('v1_test123', $log->external_request_id);
    }

    public function test_source_missing_invalid_unsupported(): void
    {
        Storage::fake('public');
        $this->enableVideo();
        $this->fakeOmni($this->videoBlock(base64_encode(self::MP4_FAKE)));
        $this->fakeInspector();
        $script = $this->context();

        $missing = $this->sourceAsset($script);
        Storage::disk('public')->delete($missing->path);
        $reqMissing = app(VideoGenerationService::class)->createRequest(
            'MOTION - A cat walks slowly toward the camera.',
            ['content_script_id' => $script->id, 'source_media_asset_id' => $missing->id],
            null,
        );
        (new GenerateVideoJob($reqMissing->id))->handle(app(VideoGenerationService::class));
        $this->assertSame('source_missing', $reqMissing->fresh()->error_code);

        $invalid = $this->sourceAsset($script);
        Storage::disk('public')->put($invalid->path, 'lixo');
        $reqInvalid = app(VideoGenerationService::class)->createRequest(
            'MOTION - A cat walks slowly toward the camera.',
            ['content_script_id' => $script->id, 'source_media_asset_id' => $invalid->id],
            null,
        );
        (new GenerateVideoJob($reqInvalid->id))->handle(app(VideoGenerationService::class));
        $this->assertSame('source_invalid', $reqInvalid->fresh()->error_code);

        $gif = $this->sourceAsset($script);
        Storage::disk('public')->put($gif->path, (string) hex2bin('47494638396101000100800000'));
        $reqGif = app(VideoGenerationService::class)->createRequest(
            'MOTION - A cat walks slowly toward the camera.',
            ['content_script_id' => $script->id, 'source_media_asset_id' => $gif->id],
            null,
        );
        (new GenerateVideoJob($reqGif->id))->handle(app(VideoGenerationService::class));
        $this->assertSame('source_unsupported', $reqGif->fresh()->error_code);

        Http::assertNothingSent();
        $this->assertDatabaseCount('media_assets', 3);
    }

    public function test_video_invalido_e_temp_limpo(): void
    {
        Storage::fake('public');
        $this->enableVideo();
        $this->fakeOmni($this->videoBlock(base64_encode(self::MP4_FAKE)));
        $this->fakeInspector(false);
        $script = $this->context();
        $source = $this->sourceAsset($script);

        $request = app(VideoGenerationService::class)->createRequest(
            'MOTION - A cat walks slowly toward the camera.',
            ['content_script_id' => $script->id, 'source_media_asset_id' => $source->id],
            null,
        );
        (new GenerateVideoJob($request->id))->handle(app(VideoGenerationService::class));

        $this->assertSame('invalid_video', $request->fresh()->error_code);
        $this->assertDatabaseCount('media_assets', 1);
        $this->assertSame([], glob((string) storage_path('app/tmp/video-generation/*')) ?: []);
    }

    public function test_job_idempotente_e_hard_failed(): void
    {
        Storage::fake('public');
        $this->enableVideo();
        $this->fakeOmni($this->videoBlock(base64_encode(self::MP4_FAKE)));
        $this->fakeInspector();
        $script = $this->context();
        $source = $this->sourceAsset($script);

        $request = app(VideoGenerationService::class)->createRequest(
            'MOTION - A cat walks slowly toward the camera.',
            ['content_script_id' => $script->id, 'source_media_asset_id' => $source->id],
            null,
        );
        $job = new GenerateVideoJob($request->id);
        $job->handle(app(VideoGenerationService::class));
        $job->handle(app(VideoGenerationService::class));

        $this->assertSame(1, MediaAsset::where('parent_media_asset_id', $source->id)->count());

        $pending = VideoGenerationRequest::factory()->create(['status' => VideoGenerationRequestStatus::Processing]);
        (new GenerateVideoJob($pending->id))->failed();
        $this->assertSame('timeout', $pending->fresh()->error_code);
    }

    // ---- contextual / admin / segurança ----

    public function test_contextual_guest_draft_foreign(): void
    {
        $script = $this->context();
        $asset = MediaAsset::factory()->create();
        $script->mediaAssets()->attach($asset->id, ['purpose' => 'scene', 'is_primary' => false]);

        $this->get(route('scripts.videos.create', [$script, 'source' => $asset->id]))->assertRedirect('/login');
        $this->post(route('scripts.videos.store', $script))->assertRedirect('/login');

        $user = User::factory()->create();
        $foreign = MediaAsset::factory()->create();
        $this->actingAs($user)->get(route('scripts.videos.create', [$script, 'source' => $foreign->id]))->assertNotFound();

        $script->update(['status' => ContentScriptStatus::Draft]);
        $this->actingAs($user)->get(route('scripts.videos.create', [$script, 'source' => $asset->id]))->assertForbidden();
    }

    public function test_contextual_post_cria_e_output_no_script(): void
    {
        Storage::fake('public');
        $this->enableVideo();
        Queue::fake();
        $user = User::factory()->create();
        $script = $this->context();
        $source = $this->sourceAsset($script);

        $this->actingAs($user)->post(
            route('scripts.videos.store', $script),
            $this->validPost(['source_media_asset_id' => $source->id])
        )->assertRedirect(route('scripts.show', $script));

        $request = VideoGenerationRequest::firstOrFail();
        $this->assertSame($source->id, $request->source_media_asset_id);
        $this->assertSame($script->id, $request->content_script_id);
        $this->assertSame('9:16', $request->aspect_ratio);
        $this->assertSame(8, $request->duration_seconds);
        Queue::assertPushed(GenerateVideoJob::class);

        $this->withoutVite()->actingAs($user)->get(route('scripts.videos.create', [$script, 'source' => $source->id]))
            ->assertOk()
            ->assertSee('Imagem base', false)
            ->assertSee('Movimento desejado', false)
            ->assertSee('9:16 vertical', false)
            ->assertSee('Clipe curto (~8–10s)', false)
            ->assertSee('segundo plano', false)
            ->assertDontSee('clipe curto de 8s', false)
            ->assertSee('Gerar vídeo', false);
    }

    public function test_admin_page_auth(): void
    {
        $this->get(route('settings.ai.videos'))->assertRedirect('/login');

        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $this->actingAs($operator)->get(route('settings.ai.videos'))->assertForbidden();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->withoutVite()->actingAs($admin)->get(route('settings.ai.videos'))
            ->assertOk()
            ->assertSee('Google Omni', false)
            ->assertSee('Gerar vídeo de teste', false);
    }

    public function test_builder_e_seguranca(): void
    {
        $script = $this->context();
        $source = MediaAsset::factory()->create();

        $prompt = app(VideoPromptBuilder::class)->build(
            'Make the cat walk forward.',
            $script, $script->product, $script->blueprint, $script->persona, $script->avatar, $source,
        );

        $this->assertStringContainsString('MOTION', $prompt);
        $this->assertStringContainsString('Animate the provided starting image', $prompt);
        $this->assertStringContainsString('fictional AI character', $prompt);

        Storage::fake('public');
        $this->enableVideo();
        $this->fakeOmni($this->videoBlock(base64_encode(self::MP4_FAKE)));
        $this->fakeInspector();
        $script2 = $this->context();
        $src2 = $this->sourceAsset($script2);
        $binary = $this->png600();
        Storage::disk('public')->put($src2->path, $binary);

        $request = app(VideoGenerationService::class)->createRequest(
            'MOTION - A cat walks slowly toward the camera.',
            ['content_script_id' => $script2->id, 'source_media_asset_id' => $src2->id],
            null,
        );
        (new GenerateVideoJob($request->id))->handle(app(VideoGenerationService::class));

        $dump = json_encode([
            VideoGenerationRequest::firstOrFail()->toArray(),
            AiGeneration::firstWhere('operation', 'video_generation')->toArray(),
            MediaAsset::where('parent_media_asset_id', $src2->id)->firstOrFail()->toArray(),
        ]);
        $this->assertStringNotContainsString(base64_encode($binary), (string) $dump);
        $this->assertStringNotContainsString('test-auth-key', (string) $dump);
    }

    public function test_output_usa_duracao_real_do_inspector(): void
    {
        Storage::fake('public');
        $this->enableVideo();
        $this->fakeOmni($this->videoBlock(base64_encode(self::MP4_FAKE)));
        $this->fakeInspector(true, 10.0);
        $script = $this->context();
        $source = $this->sourceAsset($script);

        $request = app(VideoGenerationService::class)->createRequest(
            'MOTION - A cat walks slowly toward the camera.',
            ['content_script_id' => $script->id, 'source_media_asset_id' => $source->id],
            null,
        );
        (new GenerateVideoJob($request->id))->handle(app(VideoGenerationService::class));

        // Request pediu 8s; inspector mediu 10s; asset usa o real.
        $this->assertSame(8, $request->fresh()->duration_seconds);
        $output = MediaAsset::where('parent_media_asset_id', $source->id)->firstOrFail();
        $this->assertSame(10, $output->duration_seconds);
    }

    public function test_cards_usam_duracao_real_e_nao_prometem(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $script = $this->context();
        $source = $this->sourceAsset($script);

        VideoGenerationRequest::factory()->create([
            'content_script_id' => $script->id,
            'source_media_asset_id' => $source->id,
            'status' => VideoGenerationRequestStatus::Processing,
        ]);
        $output = MediaAsset::factory()->create([
            'type' => MediaAssetType::Video,
            'mime_type' => 'video/mp4',
            'width' => 720,
            'height' => 1280,
            'duration_seconds' => 10,
            'parent_media_asset_id' => $source->id,
        ]);
        VideoGenerationRequest::factory()->create([
            'content_script_id' => $script->id,
            'source_media_asset_id' => $source->id,
            'status' => VideoGenerationRequestStatus::Success,
            'media_asset_id' => $output->id,
        ]);

        $this->withoutVite()->actingAs($user)->get(route('scripts.show', $script))
            ->assertOk()
            ->assertSee('10s · 9:16', false)
            ->assertSee('Clipe curto · 9:16', false);
    }

    public function test_orientation_class(): void
    {
        $portrait = MediaAsset::factory()->make(['width' => 720, 'height' => 1280]);
        $landscape = MediaAsset::factory()->make(['width' => 1280, 'height' => 720]);
        $square = MediaAsset::factory()->make(['width' => 640, 'height' => 640]);
        $unknown = MediaAsset::factory()->make(['width' => null, 'height' => null]);

        $this->assertSame('mx-auto aspect-[9/16] max-w-44', $portrait->orientationClass());
        $this->assertSame('aspect-video', $landscape->orientationClass());
        $this->assertSame('aspect-square', $square->orientationClass());
        $this->assertSame('aspect-square', $unknown->orientationClass());
    }
}
