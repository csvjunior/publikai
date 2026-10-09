<?php

namespace Tests\Feature\AI;

use App\Enums\ContentScriptStatus;
use App\Enums\MediaAssetType;
use App\Enums\VideoCompositionRequestStatus;
use App\Jobs\ComposeVideoJob;
use App\Models\Avatar;
use App\Models\ContentBlueprint;
use App\Models\ContentScript;
use App\Models\MediaAsset;
use App\Models\Persona;
use App\Models\Product;
use App\Models\User;
use App\Models\VideoCompositionRequest;
use App\Services\CompositionPlan;
use App\Services\CompositionSegment;
use App\Services\FfmpegVideoComposer;
use App\Services\VideoComposer;
use App\Services\VideoCompositionException;
use App\Services\VideoCompositionService;
use App\Services\VideoInspector;
use App\Services\VideoMetadata;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Composição local de vídeo (Sprint 5.6.1, FFmpeg).
 * Domínio, trims, plano/args, Job, storage, UI/auth, segurança.
 * FFmpeg real nunca executa na suíte (FakeVideoComposer).
 */
class VideoCompositionTest extends TestCase
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

    private function imageAsset(?ContentScript $script = null): MediaAsset
    {
        $asset = MediaAsset::factory()->create(['mime_type' => 'image/png']);
        Storage::disk('public')->put($asset->path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
        ));

        if ($script) {
            $script->mediaAssets()->attach($asset->id, ['purpose' => 'scene', 'is_primary' => false]);
        }

        return $asset;
    }

    private function videoAsset(?ContentScript $script = null, int $duration = 5): MediaAsset
    {
        $asset = MediaAsset::factory()->create([
            'type' => MediaAssetType::Video,
            'mime_type' => 'video/mp4',
            'duration_seconds' => $duration,
        ]);
        Storage::disk('public')->put($asset->path, 'fake-video-bytes');

        if ($script) {
            $script->mediaAssets()->attach($asset->id, ['purpose' => 'scene', 'is_primary' => false]);
        }

        return $asset;
    }

    private function fakeInspector(): void
    {
        $this->app->bind(VideoInspector::class, fn () => new class implements VideoInspector
        {
            public function inspect(string $path): ?VideoMetadata
            {
                return new VideoMetadata('video/mp4', 720, 1280, 5.0, 9999);
            }
        });
    }

    private function fakeComposer(bool $succeed = true): void
    {
        $this->app->bind(VideoComposer::class, fn () => new class($succeed) implements VideoComposer
        {
            public function __construct(private bool $succeed) {}

            public function compose(CompositionPlan $plan, string $workDir, string $outputPath): void
            {
                if (! $this->succeed) {
                    throw new VideoCompositionException('ffmpeg_failed', 'Falha ao compor o vídeo.');
                }

                if (! is_dir($workDir)) {
                    mkdir($workDir, 0755, true);
                }

                file_put_contents($outputPath, 'fake-composed-mp4');
            }
        });
    }

    private function items(MediaAsset ...$assets): array
    {
        return collect($assets)->values()->map(fn ($a, $i) => [
            'media_asset_id' => $a->id,
            'position' => $i + 1,
        ])->all();
    }

    // ---- domínio ----

    public function test_create_ordenado_e_snapshot(): void
    {
        Storage::fake('public');
        $script = $this->context();
        $a = $this->videoAsset($script);
        $b = $this->imageAsset($script);

        $request = app(VideoCompositionService::class)->createRequest($script->id, [
            ['media_asset_id' => $b->id, 'position' => 2, 'image_duration_s' => 3],
            ['media_asset_id' => $a->id, 'position' => 1, 'trim_start_s' => 1, 'trim_end_s' => 3],
        ]);

        $inputs = $request->inputs()->orderBy('position')->get();
        $this->assertSame([$a->id, $b->id], $inputs->pluck('media_asset_id')->all());
        $this->assertSame(1000, $inputs[0]->trim_start_ms);
        $this->assertSame(3000, $inputs[0]->trim_end_ms);
        $this->assertSame(3000, $inputs[1]->image_duration_ms);
    }

    public function test_create_rejeita(): void
    {
        Storage::fake('public');
        $script = $this->context();
        $a = $this->videoAsset($script);
        $foreign = MediaAsset::factory()->create(['type' => MediaAssetType::Video]);
        $service = app(VideoCompositionService::class);

        // Asset de outro script.
        try {
            $service->createRequest($script->id, $this->items($foreign));
            $this->fail('deveria lançar');
        } catch (\Throwable) {
            $this->assertTrue(true);
        }

        // Posições duplicadas.
        $this->expectException(ValidationException::class);
        $service->createRequest($script->id, [
            ['media_asset_id' => $a->id, 'position' => 1],
            ['media_asset_id' => $a->id, 'position' => 1],
        ]);
    }

    public function test_max_inputs_e_duration_limit(): void
    {
        Storage::fake('public');
        $script = $this->context();
        $assets = [];
        for ($i = 0; $i < 11; $i++) {
            $assets[] = $this->imageAsset($script);
        }

        try {
            app(VideoCompositionService::class)->createRequest($script->id, $this->items(...$assets));
            $this->fail('deveria lançar');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('assets', $e->errors());
        }

        config()->set('video-composition.max_duration_seconds', 1);

        try {
            app(VideoCompositionService::class)->createRequest($script->id, $this->items($assets[0]));
            $this->fail('deveria lançar');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('assets', $e->errors());
        }
    }

    // ---- trims / imagem ----

    public function test_trims_invalidos(): void
    {
        Storage::fake('public');
        $script = $this->context();
        $a = $this->videoAsset($script, 5);
        $service = app(VideoCompositionService::class);

        foreach ([
            ['trim_start_s' => 3, 'trim_end_s' => 2],
            ['trim_start_s' => 0, 'trim_end_s' => 99],
            ['trim_start_s' => -1, 'trim_end_s' => 2],
        ] as $trim) {
            try {
                $service->createRequest($script->id, [array_merge(['media_asset_id' => $a->id, 'position' => 1], $trim)]);
                $this->fail('deveria lançar: '.json_encode($trim));
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }

        // Sem trim: vídeo inteiro.
        $request = $service->createRequest($script->id, $this->items($a));
        $this->assertSame(0, $request->inputs()->first()->trim_start_ms);
        $this->assertSame(5000, $request->inputs()->first()->trim_end_ms);
    }

    public function test_image_duration_default_range(): void
    {
        Storage::fake('public');
        $script = $this->context();
        $img = $this->imageAsset($script);
        $service = app(VideoCompositionService::class);

        $default = $service->createRequest($script->id, $this->items($img));
        $this->assertSame(3000, $default->inputs()->first()->image_duration_ms);

        try {
            $service->createRequest($script->id, [['media_asset_id' => $img->id, 'position' => 1, 'image_duration_s' => 99]]);
            $this->fail('deveria lançar');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
    }

    // ---- plano/args ----

    public function test_normalize_arguments(): void
    {
        $composer = new FfmpegVideoComposer;
        $plan = new CompositionPlan([
            new CompositionSegment('/tmp/a.mp4', 'video', 1000, 3000),
            new CompositionSegment('/tmp/b.png', 'image', null, null, 3000),
        ], 720, 1280, 30);

        $videoArgs = $composer->normalizeArguments($plan, $plan->segments[0], '/tmp/out0.mp4');
        $imageArgs = $composer->normalizeArguments($plan, $plan->segments[1], '/tmp/out1.mp4');

        foreach ([$videoArgs, $imageArgs] as $args) {
            $flat = implode(' ', $args);
            $this->assertStringContainsString('scale=720:1280', $flat);
            $this->assertStringContainsString('pad=720:1280', $flat);
            $this->assertStringContainsString('libx264', $flat);
            $this->assertStringContainsString('yuv420p', $flat);
            $this->assertStringContainsString('-an', $flat);
            $this->assertStringContainsString('fps=30', $flat);
        }

        $this->assertContains('-ss', $videoArgs);
        $this->assertContains('-loop', $imageArgs);
        $this->assertStringNotContainsString(';', implode(' ', $videoArgs));
    }

    // ---- job / storage ----

    public function test_job_success_e_storage(): void
    {
        Storage::fake('public');
        $script = $this->context();
        $a = $this->videoAsset($script);
        $b = $this->imageAsset($script);
        $this->fakeComposer();
        $this->fakeInspector();

        $request = app(VideoCompositionService::class)->createRequest($script->id, $this->items($a, $b));
        (new ComposeVideoJob($request->id))->handle(app(VideoCompositionService::class));

        $request = $request->fresh();
        $this->assertSame(VideoCompositionRequestStatus::Success, $request->status);

        $output = MediaAsset::findOrFail($request->output_media_asset_id);
        $this->assertSame('video', $output->type->value);
        $this->assertSame('composed', $output->source->value);
        $this->assertStringStartsWith('videos/compositions/', $output->path);
        Storage::disk('public')->assertExists($output->path);

        // Proveniência: request + inputs (sem parent único).
        $this->assertSame(2, $request->inputs()->count());
        $this->assertDatabaseHas('media_assets', ['id' => $a->id]);
        $this->assertSame([], glob((string) storage_path('app/tmp/video-composition/*')) ?: []);
    }

    public function test_job_ffmpeg_failed_e_idempotencia(): void
    {
        Storage::fake('public');
        $script = $this->context();
        $a = $this->videoAsset($script);
        $this->fakeComposer(false);
        $this->fakeInspector();

        $request = app(VideoCompositionService::class)->createRequest($script->id, $this->items($a));
        (new ComposeVideoJob($request->id))->handle(app(VideoCompositionService::class));

        $this->assertSame('ffmpeg_failed', $request->fresh()->error_code);
        $this->assertNull($request->fresh()->output_media_asset_id);
        $this->assertSame([], glob((string) storage_path('app/tmp/video-composition/*')) ?: []);

        // Idempotência: terminal não reprocessa.
        (new ComposeVideoJob($request->id))->handle(app(VideoCompositionService::class));
        $this->assertSame(0, MediaAsset::where('source', 'composed')->count());

        $pending = VideoCompositionRequest::factory()->create(['status' => VideoCompositionRequestStatus::Processing]);
        (new ComposeVideoJob($pending->id))->failed();
        $this->assertSame('timeout', $pending->fresh()->error_code);
    }

    // ---- UI/auth/segurança ----

    public function test_composer_ui_auth(): void
    {
        $script = $this->context();

        $this->get(route('scripts.compositions.create', $script))->assertRedirect('/login');
        $this->post(route('scripts.compositions.store', $script))->assertRedirect('/login');

        $user = User::factory()->create();
        $foreign = MediaAsset::factory()->create(['type' => MediaAssetType::Video]);
        $this->actingAs($user)->post(route('scripts.compositions.store', $script), [
            'items' => [['media_asset_id' => $foreign->id, 'position' => 1]],
        ])->assertNotFound();

        $script->update(['status' => ContentScriptStatus::Draft]);
        $this->actingAs($user)->get(route('scripts.compositions.create', $script))->assertForbidden();
    }

    public function test_composer_post_e_show(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $script = $this->context();
        $a = $this->videoAsset($script);
        $b = $this->imageAsset($script);

        $this->withoutVite()->actingAs($user)->get(route('scripts.compositions.create', $script))
            ->assertOk()
            ->assertSee('Montar vídeo', false)
            ->assertSee('Posição', false)
            ->assertSee('Resumo da montagem', false)
            ->assertSee('composition-summary', false)
            ->assertSee('Vídeo ·', false)
            ->assertSee('Imagem ·', false)
            ->assertDontSee('#'.$a->id.' ·', false)
            ->assertDontSee('#'.$b->id.' ·', false);

        Queue::fake();
        $this->actingAs($user)->post(route('scripts.compositions.store', $script), [
            'items' => [
                ['media_asset_id' => $a->id, 'position' => 1, 'trim_start_s' => 0, 'trim_end_s' => 2],
                ['media_asset_id' => $b->id, 'position' => 2, 'image_duration_s' => 3],
            ],
        ])->assertRedirect(route('scripts.show', $script));

        $this->assertDatabaseCount('video_composition_requests', 1);
        Queue::assertPushed(ComposeVideoJob::class);

        $this->withoutVite()->actingAs($user)->get(route('scripts.show', $script))
            ->assertOk()
            ->assertSee('Montar vídeo', false)
            ->assertSee('Montagem', false)
            ->assertSee('<video', false);
    }

    public function test_sem_shell_injection(): void
    {
        Storage::fake('public');
        $script = $this->context();
        $a = $this->videoAsset($script);

        // Nome/posição maliciosos não viram comando: position inválida rejeitada.
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('scripts.compositions.store', $script), [
            'items' => [['media_asset_id' => $a->id, 'position' => '1; rm -rf /']],
        ])->assertSessionHasErrors();

        $this->assertDatabaseCount('video_composition_requests', 0);
    }
}
