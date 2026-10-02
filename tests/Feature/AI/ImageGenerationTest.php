<?php

namespace Tests\Feature\AI;

use App\Enums\ImageGenerationRequestStatus;
use App\Enums\UserRole;
use App\Jobs\GenerateImageJob;
use App\Models\AiGeneration;
use App\Models\ImageGenerationRequest;
use App\Models\MediaAsset;
use App\Models\User;
use App\Services\ImageGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageGenerationTest extends TestCase
{
    use RefreshDatabase;

    // PNG 1x1 transparente (fixture pública mínima).
    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    /**
     * JPEG 1x1 mínimo construído manualmente (só cabeçalhos; suficiente para
     * getimagesizefromstring detectar dimensões e MIME sem GD).
     */
    private function jpeg1x1(): string
    {
        $soi = "\xFF\xD8";
        $app0 = "\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00";
        $dqt = "\xFF\xDB\x00\x43\x00".str_repeat("\x08", 64);
        $sof = "\xFF\xC0\x00\x11\x08\x00\x01\x00\x01\x03\x01\x11\x00\x02\x11\x01\x03\x11\x01";
        $sos = "\xFF\xDA\x00\x0C\x03\x01\x00\x02\x11\x03\x11\x00\x3F\x00";
        $eoi = "\xFF\xD9";

        return base64_encode($soi.$app0.$dqt.$sof.$sos."\x00".$eoi);
    }

    private function enableImage(): void
    {
        config()->set('ai.google.image.enabled', true);
        config()->set('ai.google.auth_key', 'test-auth-key');
    }

    private function fakeImage(string $base64, string $mime = 'image/jpeg'): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'id' => 'interaction-img-1',
            'status' => 'completed',
            'steps' => [
                [
                    'type' => 'model_output',
                    'content' => [
                        ['type' => 'image', 'data' => $base64, 'mime_type' => $mime],
                    ],
                ],
            ],
        ], 200)]);
    }

    private function validPost(array $overrides = []): array
    {
        return array_merge([
            'prompt' => 'Photorealistic vertical UGC-style beauty product scene, natural daylight.',
            'aspect_ratio' => '9:16',
            'image_size' => '1K',
            'mime_type' => 'image/jpeg',
        ], $overrides);
    }

    private function runJob(ImageGenerationRequest $request): void
    {
        (new GenerateImageJob($request->id))->handle(app(ImageGenerationService::class));
    }

    public function test_guest_bloqueado(): void
    {
        $this->get('/settings/ai/images')->assertRedirect('/login');
        $this->post('/settings/ai/images', $this->validPost())->assertRedirect('/login');
    }

    public function test_operator_403(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);

        $this->actingAs($operator)->get('/settings/ai/images')->assertForbidden();
        $this->actingAs($operator)->post('/settings/ai/images', $this->validPost())->assertForbidden();
    }

    public function test_admin_acessa(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        config()->set('ai.google.image.enabled', false);

        $this->withoutVite()->actingAs($admin)->get('/settings/ai/images')
            ->assertOk()
            ->assertSee('Nano Banana 2', false)
            ->assertSee('Não configurado', false);
    }

    public function test_admin_post_cria_pending_e_dispatch_sem_executar(): void
    {
        $this->enableImage();
        Queue::fake();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->post('/settings/ai/images', $this->validPost());

        $response->assertRedirect(route('settings.ai.images'));
        $this->assertDatabaseHas('image_generation_requests', ['status' => 'pending']);
        Queue::assertPushed(GenerateImageJob::class);
        Http::assertNothingSent();
        $this->assertDatabaseCount('media_assets', 0);
    }

    public function test_provider_disabled(): void
    {
        config()->set('ai.google.image.enabled', false);
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->post('/settings/ai/images', $this->validPost());

        $response->assertRedirect();
        $response->assertSessionHas('image_test');
        $this->assertFalse(session('image_test')['ok']);
        $this->assertDatabaseCount('image_generation_requests', 0);
        Http::assertNothingSent();
    }

    public function test_credential_missing(): void
    {
        config()->set('ai.google.image.enabled', true);
        config()->set('ai.google.auth_key', '');
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->post('/settings/ai/images', $this->validPost());

        $response->assertRedirect();
        $this->assertFalse(session('image_test')['ok']);
        $this->assertDatabaseCount('image_generation_requests', 0);
        Http::assertNothingSent();
    }

    public function test_job_success_jpeg(): void
    {
        $this->enableImage();
        $this->fakeImage($this->jpeg1x1());
        Storage::fake('public');
        $request = ImageGenerationRequest::factory()->create();

        $this->runJob($request);

        $request->refresh();
        $this->assertSame(ImageGenerationRequestStatus::Success, $request->status);
        $this->assertNotNull($request->completed_at);

        $asset = MediaAsset::firstOrFail();
        $this->assertSame($request->media_asset_id, $asset->id);
        $this->assertSame('image/jpeg', $asset->mime_type);
        $this->assertSame('jpg', pathinfo($asset->path, PATHINFO_EXTENSION));
        $this->assertStringStartsWith('images/', $asset->path);
        $this->assertSame(1, $asset->width);
        $this->assertSame(1, $asset->height);
        $this->assertGreaterThan(0, $asset->size_bytes);
        Storage::disk('public')->assertExists($asset->path);

        $this->assertDatabaseHas('ai_generations', [
            'operation' => 'image_generation',
            'status' => 'success',
        ]);
    }

    public function test_job_success_png(): void
    {
        $this->enableImage();
        $this->fakeImage(self::PNG_1X1, 'image/png');
        Storage::fake('public');
        $request = ImageGenerationRequest::factory()->create(['mime_type' => 'image/png']);

        $this->runJob($request);

        $asset = MediaAsset::firstOrFail();
        $this->assertSame('image/png', $asset->mime_type);
        $this->assertSame('png', pathinfo($asset->path, PATHINFO_EXTENSION));
        Storage::disk('public')->assertExists($asset->path);
    }

    public function test_job_text_e_image_juntos(): void
    {
        $this->enableImage();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'id' => 'interaction-img-2',
            'steps' => [
                ['type' => 'model_output', 'content' => [['type' => 'text', 'text' => 'Aqui está:']]],
                ['type' => 'model_output', 'content' => [['type' => 'image', 'data' => self::PNG_1X1, 'mime_type' => 'image/png']]],
            ],
        ], 200)]);
        Storage::fake('public');
        $request = ImageGenerationRequest::factory()->create(['mime_type' => 'image/png']);

        $this->runJob($request);

        $this->assertSame(ImageGenerationRequestStatus::Success, $request->fresh()->status);
        $this->assertSame('image/png', MediaAsset::firstOrFail()->mime_type);
    }

    public function test_job_invalid_base64(): void
    {
        $this->enableImage();
        $this->fakeImage('!!!nao-e-base64!!!');
        Storage::fake('public');
        $request = ImageGenerationRequest::factory()->create();

        $this->runJob($request);

        $this->assertSame(ImageGenerationRequestStatus::Failed, $request->fresh()->status);
        $this->assertSame('invalid_image', $request->fresh()->error_code);
        $this->assertDatabaseCount('media_assets', 0);
    }

    public function test_job_empty_image(): void
    {
        $this->enableImage();
        $this->fakeImage(base64_encode('texto que não é imagem'));
        Storage::fake('public');
        $request = ImageGenerationRequest::factory()->create();

        $this->runJob($request);

        $this->assertSame(ImageGenerationRequestStatus::Failed, $request->fresh()->status);
        $this->assertDatabaseCount('media_assets', 0);
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_job_429(): void
    {
        $this->enableImage();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'x'], 429)]);
        Storage::fake('public');
        $request = ImageGenerationRequest::factory()->create();

        $this->runJob($request);

        $this->assertSame('rate_limited', $request->fresh()->error_code);
        $this->assertDatabaseCount('media_assets', 0);
        Http::assertSentCount(1);
    }

    public function test_job_503(): void
    {
        $this->enableImage();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'x'], 503)]);
        Storage::fake('public');
        $request = ImageGenerationRequest::factory()->create();

        $this->runJob($request);

        $this->assertSame('service_unavailable', $request->fresh()->error_code);
        $this->assertDatabaseCount('media_assets', 0);
        Http::assertSentCount(1);
    }

    public function test_job_timeout(): void
    {
        $this->enableImage();
        Http::fake(function () {
            throw new ConnectionException('timeout');
        });
        Storage::fake('public');
        $request = ImageGenerationRequest::factory()->create();

        $this->runJob($request);

        $this->assertSame('timeout', $request->fresh()->error_code);
        $this->assertDatabaseCount('media_assets', 0);
    }

    public function test_job_nao_reprocessa_terminal(): void
    {
        $this->enableImage();
        $this->fakeImage($this->jpeg1x1());
        Storage::fake('public');

        $success = ImageGenerationRequest::factory()->create(['status' => ImageGenerationRequestStatus::Success]);
        $failed = ImageGenerationRequest::factory()->create(['status' => ImageGenerationRequestStatus::Failed]);

        $this->runJob($success);
        $this->runJob($failed);

        Http::assertNothingSent();
        $this->assertDatabaseCount('media_assets', 0);
        $this->assertDatabaseCount('ai_generations', 0);
    }

    public function test_um_request_um_asset(): void
    {
        $this->enableImage();
        $this->fakeImage($this->jpeg1x1());
        Storage::fake('public');
        $request = ImageGenerationRequest::factory()->create();

        $this->runJob($request);
        $firstId = $request->fresh()->media_asset_id;

        $this->runJob($request->fresh());

        $this->assertSame($firstId, $request->fresh()->media_asset_id);
        $this->assertSame(1, MediaAsset::count());
    }

    public function test_segredo_prompt_base64_ausentes(): void
    {
        $this->enableImage();
        $this->fakeImage($this->jpeg1x1());
        Storage::fake('public');
        $request = ImageGenerationRequest::factory()->create(['prompt' => 'PROMPT-SECRETO-XYZ']);

        $this->runJob($request);

        $asset = MediaAsset::firstOrFail();
        $dump = json_encode([$asset->path, $asset->filename, $asset->metadata]);
        $this->assertStringNotContainsString('test-auth-key', $dump);
        $this->assertStringNotContainsString('PROMPT-SECRETO-XYZ', $dump);
        $this->assertStringNotContainsString($this->jpeg1x1(), $dump);

        $log = AiGeneration::firstWhere('operation', 'image_generation');
        $logDump = json_encode([$log->error_code, $log->metadata, $log->external_request_id]);
        $this->assertStringNotContainsString('test-auth-key', (string) $logDump);
        $this->assertStringNotContainsString('PROMPT-SECRETO-XYZ', (string) $logDump);
    }

    public function test_ui_estados(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ImageGenerationRequest::factory()->create(['status' => ImageGenerationRequestStatus::Pending, 'prompt' => 'Um prompt qualquer']);
        ImageGenerationRequest::factory()->create(['status' => ImageGenerationRequestStatus::Processing]);
        ImageGenerationRequest::factory()->create(['status' => ImageGenerationRequestStatus::Failed, 'error_code' => 'timeout']);

        $response = $this->withoutVite()->actingAs($admin)->get('/settings/ai/images')->assertOk();

        $response->assertSee('Pendente', false);
        $response->assertSee('Processando', false);
        $response->assertSee('Falhou', false);
        $response->assertSee('Gerações recentes', false);
    }

    public function test_legacy_output_image_fallback(): void
    {
        $this->enableImage();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'id' => 'interaction-img-3',
            'output_image' => ['data' => self::PNG_1X1, 'mime_type' => 'image/png'],
        ], 200)]);
        Storage::fake('public');
        $request = ImageGenerationRequest::factory()->create(['mime_type' => 'image/png']);

        $this->runJob($request);

        $this->assertSame('image/png', MediaAsset::firstOrFail()->mime_type);
    }

    public function test_sem_image_block(): void
    {
        $this->enableImage();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'id' => 'interaction-img-4',
            'steps' => [
                ['type' => 'model_output', 'content' => [['type' => 'text', 'text' => 'sem imagem']]],
            ],
        ], 200)]);
        Storage::fake('public');
        $request = ImageGenerationRequest::factory()->create();

        $this->runJob($request);

        $this->assertSame('invalid_image', $request->fresh()->error_code);
        $this->assertDatabaseCount('media_assets', 0);
    }

    public function test_mime_type_ausente_detectado_pelo_binario(): void
    {
        $this->enableImage();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'id' => 'interaction-img-5',
            'steps' => [
                ['type' => 'model_output', 'content' => [['type' => 'image', 'data' => self::PNG_1X1]]],
            ],
        ], 200)]);
        Storage::fake('public');
        $request = ImageGenerationRequest::factory()->create(['mime_type' => 'image/png']);

        $this->runJob($request);

        $this->assertSame('image/png', MediaAsset::firstOrFail()->mime_type);
    }

    public function test_external_request_id(): void
    {
        $this->enableImage();
        $this->fakeImage($this->jpeg1x1());
        Storage::fake('public');
        $request = ImageGenerationRequest::factory()->create();

        $this->runJob($request);

        $this->assertDatabaseHas('ai_generations', [
            'operation' => 'image_generation',
            'status' => 'success',
            'external_request_id' => 'interaction-img-1',
        ]);
    }

    public function test_job_config_tries_timeout(): void
    {
        $job = new GenerateImageJob(1);

        $this->assertSame(1, $job->tries);
        $this->assertSame(90, $job->timeout);
        $this->assertSame(60, config('ai.google.image.timeout'));
    }

    public function test_failed_marca_request_preso(): void
    {
        $request = ImageGenerationRequest::factory()->create([
            'status' => ImageGenerationRequestStatus::Processing,
        ]);

        (new GenerateImageJob($request->id))->failed();

        $this->assertSame(ImageGenerationRequestStatus::Failed, $request->fresh()->status);
        $this->assertSame('timeout', $request->fresh()->error_code);
    }

    public function test_failed_preserva_terminal(): void
    {
        $request = ImageGenerationRequest::factory()->create([
            'status' => ImageGenerationRequestStatus::Success,
        ]);

        (new GenerateImageJob($request->id))->failed();

        $this->assertSame(ImageGenerationRequestStatus::Success, $request->fresh()->status);
        $this->assertNull($request->fresh()->error_code);
    }
}
