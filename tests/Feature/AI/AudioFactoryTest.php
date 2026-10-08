<?php

namespace Tests\Feature\AI;

use App\AI\AiAudioGenerationInput;
use App\AI\Contracts\AiAudioProvider;
use App\AI\Exceptions\AiProviderException;
use App\Enums\AudioGenerationRequestStatus;
use App\Enums\ContentScriptStatus;
use App\Enums\UserRole;
use App\Jobs\GenerateAudioJob;
use App\Models\AiGeneration;
use App\Models\AudioGenerationRequest;
use App\Models\Avatar;
use App\Models\ContentBlueprint;
use App\Models\ContentScript;
use App\Models\MediaAsset;
use App\Models\Persona;
use App\Models\Product;
use App\Models\User;
use App\Services\AudioGenerationService;
use App\Services\AudioInspector;
use App\Services\AudioMetadata;
use App\Services\NarrationTextBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Fundação de narração TTS (Sprint 5.6.2): provider oficial, texto, voz,
 * Job, inspector, storage, UI/auth, segurança. Sem rede real.
 */
class AudioFactoryTest extends TestCase
{
    use RefreshDatabase;

    private const WAV_FAKE = 'fake-wav-binary-content';

    private function enableAudio(): void
    {
        config()->set('ai.google.audio.enabled', true);
        config()->set('ai.google.auth_key', 'test-auth-key');
    }

    private function fakeInspector(bool $valid = true): void
    {
        $metadata = $valid ? new AudioMetadata('audio/wav', 12.5, 24000, 1, 600044) : null;

        $this->app->bind(AudioInspector::class, fn () => new class($metadata) implements AudioInspector
        {
            public function __construct(private ?AudioMetadata $metadata) {}

            public function inspect(string $path): ?AudioMetadata
            {
                return $this->metadata;
            }
        });
    }

    private function audioBlock(string $data, string $mime = 'audio/wav'): array
    {
        return [
            'id' => 'v1_audio1',
            'steps' => [
                ['type' => 'model_output', 'content' => [['type' => 'audio', 'data' => $data, 'mime_type' => $mime]]],
            ],
        ];
    }

    private function fakeTts(array $json, int $status = 200): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($json, $status)]);
    }

    private function context(): ContentScript
    {
        return ContentScript::factory()->create([
            'product_id' => Product::factory()->create(['language' => 'en-US', 'market' => 'US'])->id,
            'content_blueprint_id' => ContentBlueprint::factory()->create(['language' => 'en-US', 'market' => 'US'])->id,
            'persona_id' => Persona::factory()->create(['language' => 'en-US', 'market' => 'US', 'tone' => 'Warm and playful'])->id,
            'avatar_id' => Avatar::factory()->create(['language' => 'en-US', 'market' => 'US'])->id,
            'status' => ContentScriptStatus::Ready,
        ]);
    }

    // ---- provider ----

    public function test_provider_payload_oficial(): void
    {
        $this->enableAudio();
        $this->fakeTts($this->audioBlock(base64_encode(self::WAV_FAKE)));

        $result = app(AiAudioProvider::class)->generate(
            new AiAudioGenerationInput('Have a wonderful day!', 'Kore', 'cheerful and friendly')
        );

        $this->assertSame(self::WAV_FAKE, $result->audioData);
        $this->assertSame('audio/wav', $result->mimeType);
        $this->assertSame('v1_audio1', $result->externalRequestId);

        Http::assertSent(function ($request) {
            $data = $request->data();
            $content = $data['input'][0]['content'][0] ?? [];

            return ($data['model'] ?? null) === 'gemini-3.8-flash-tts'
                && $request->hasHeader('x-goog-api-key', 'test-auth-key')
                && ($data['input'][0]['type'] ?? null) === 'user_input'
                && ($content['type'] ?? null) === 'text'
                && ($content['annotations'][0]['type'] ?? null) === 'speech_metadata'
                && ($content['annotations'][0]['style'] ?? null) === 'cheerful and friendly'
                && ($data['response_format']['type'] ?? null) === 'audio'
                && ($data['generation_config']['speech_config'][0]['voice'] ?? null) === 'Kore';
        });
    }

    public function test_provider_erros(): void
    {
        $this->enableAudio();
        $provider = app(AiAudioProvider::class);
        $input = new AiAudioGenerationInput('Hello world, this is a test.', 'Kore');

        $queue = [
            ['json' => ['id' => 'x', 'steps' => []], 'status' => 200],
            ['json' => ['error' => ['message' => 'Bad.']], 'status' => 400],
            ['json' => ['error' => ['message' => 'Slow.']], 'status' => 429],
            ['json' => ['error' => ['message' => 'Down.']], 'status' => 503],
        ];
        Http::fake(function () use (&$queue) {
            $next = array_shift($queue);

            return Http::response($next['json'], $next['status']);
        });

        try {
            $provider->generate($input);
            $this->fail('deveria lançar');
        } catch (AiProviderException $e) {
            $this->assertSame('invalid_audio', $e->errorCode);
        }

        foreach (['invalid_request', 'rate_limited', 'service_unavailable'] as $code) {
            try {
                app(AiAudioProvider::class)->generate(
                    new AiAudioGenerationInput('Hello world, this is a test.', 'Kore')
                );
                $this->fail('deveria lançar');
            } catch (AiProviderException $e) {
                $this->assertSame($code, $e->errorCode);
            }
        }
    }

    // ---- texto/voz ----

    public function test_narration_builder_e_limites(): void
    {
        $script = $this->context();

        $text = app(NarrationTextBuilder::class)->build($script);
        $this->assertStringContainsString((string) $script->hook, $text);
        $this->assertStringContainsString((string) $script->body, $text);
        $this->assertStringContainsString((string) $script->cta, $text);

        // Edição manual não altera o Script.
        $this->assertDatabaseHas('content_scripts', ['id' => $script->id, 'hook' => $script->hook]);

        // Voz inválida e texto curto/longo.
        $service = app(AudioGenerationService::class);

        try {
            $service->createRequest('Texto válido para teste de voz.', ['voice' => 'Inexistente']);
            $this->fail('deveria lançar');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        try {
            $service->createRequest('curto', ['voice' => 'Kore']);
            $this->fail('deveria lançar');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
    }

    // ---- job/storage ----

    public function test_process_success_completo(): void
    {
        Storage::fake('public');
        $this->enableAudio();
        $this->fakeTts($this->audioBlock(base64_encode(self::WAV_FAKE)));
        $this->fakeInspector();
        $script = $this->context();

        $request = app(AudioGenerationService::class)->createRequest(
            'Hello world, this is a neutral test narration.',
            ['voice' => 'Kore', 'language' => 'en-US', 'style' => 'Warm and playful', 'content_script_id' => $script->id],
            null,
        );

        (new GenerateAudioJob($request->id))->handle(app(AudioGenerationService::class));

        $request = $request->fresh();
        $this->assertSame(AudioGenerationRequestStatus::Success, $request->status);
        $this->assertNotNull($request->started_at);

        $output = MediaAsset::findOrFail($request->media_asset_id);
        $this->assertSame('audio', $output->type->value);
        $this->assertSame('ai_generated', $output->source->value);
        $this->assertSame('audio/wav', $output->mime_type);
        $this->assertSame(13, $output->duration_seconds);
        $this->assertStringStartsWith('audio/', $output->path);
        $this->assertStringEndsWith('.wav', $output->path);
        Storage::disk('public')->assertExists($output->path);

        $this->assertTrue($script->fresh()->audioRequests()->whereKey($request->id)->exists());

        $log = AiGeneration::firstWhere('operation', 'audio_generation');
        $this->assertSame('success', $log->status->value);
        $this->assertSame('Kore', $log->metadata['voice']);
        $this->assertSame(24000, $log->metadata['sample_rate']);
    }

    public function test_job_failure_idempotencia_e_hard_failed(): void
    {
        Storage::fake('public');
        $this->enableAudio();
        $this->fakeTts($this->audioBlock(base64_encode(self::WAV_FAKE)));
        $this->fakeInspector(false);
        $script = $this->context();

        $request = app(AudioGenerationService::class)->createRequest(
            'Hello world, this is a neutral test narration.',
            ['voice' => 'Kore', 'content_script_id' => $script->id],
            null,
        );
        (new GenerateAudioJob($request->id))->handle(app(AudioGenerationService::class));

        $this->assertSame('invalid_audio', $request->fresh()->error_code);
        $this->assertDatabaseCount('media_assets', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('audio'));

        (new GenerateAudioJob($request->id))->handle(app(AudioGenerationService::class));
        $this->assertDatabaseCount('media_assets', 0);

        $pending = AudioGenerationRequest::factory()->create(['status' => AudioGenerationRequestStatus::Processing]);
        (new GenerateAudioJob($pending->id))->failed();
        $this->assertSame('timeout', $pending->fresh()->error_code);
    }

    // ---- UI/auth/segurança ----

    public function test_contextual_guest_draft(): void
    {
        $script = $this->context();

        $this->get(route('scripts.audio.create', $script))->assertRedirect('/login');
        $this->post(route('scripts.audio.store', $script))->assertRedirect('/login');

        $user = User::factory()->create();
        $script->update(['status' => ContentScriptStatus::Draft]);
        $this->actingAs($user)->get(route('scripts.audio.create', $script))->assertForbidden();
        $this->actingAs($user)->post(route('scripts.audio.store', $script), [
            'text' => 'Texto válido para verificar bloqueio de rascunho.',
            'voice' => 'Kore',
        ])->assertForbidden();
    }

    public function test_contextual_post_e_tela(): void
    {
        $this->enableAudio();
        Queue::fake();
        $user = User::factory()->create();
        $script = $this->context();

        $this->withoutVite()->actingAs($user)->get(route('scripts.audio.create', $script))
            ->assertOk()
            ->assertSee('Criar narração', false)
            ->assertSee('Kore · Firme', false)
            ->assertSee((string) $script->hook, false);

        $this->actingAs($user)->post(route('scripts.audio.store', $script), [
            'text' => 'Texto editado manualmente para teste.',
            'voice' => 'Puck',
        ])->assertRedirect(route('scripts.show', $script));

        $request = AudioGenerationRequest::firstOrFail();
        $this->assertSame('Texto editado manualmente para teste.', $request->text);
        $this->assertSame('Puck', $request->voice);
        $this->assertSame('en-US', $request->language);
        $this->assertSame('Warm and playful', $request->style);
        Queue::assertPushed(GenerateAudioJob::class);

        $this->withoutVite()->actingAs($user)->get(route('scripts.show', $script))
            ->assertOk()
            ->assertSee('Narrações', false)
            ->assertSee('Gerar narração', false);
    }

    public function test_admin_page_auth(): void
    {
        $this->get(route('settings.ai.audio'))->assertRedirect('/login');

        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $this->actingAs($operator)->get(route('settings.ai.audio'))->assertForbidden();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->withoutVite()->actingAs($admin)->get(route('settings.ai.audio'))
            ->assertOk()
            ->assertSee('Google Gemini TTS', false)
            ->assertSee('Gerar narração de teste', false);
    }

    public function test_seguranca_sem_texto_base64_chave(): void
    {
        Storage::fake('public');
        $this->enableAudio();
        $wav = base64_encode(self::WAV_FAKE);
        $this->fakeTts($this->audioBlock($wav));
        $this->fakeInspector();
        $script = $this->context();
        $text = 'Texto neutro de teste para auditoria de segurança.';

        $request = app(AudioGenerationService::class)->createRequest(
            $text,
            ['voice' => 'Kore', 'content_script_id' => $script->id],
            null,
        );
        (new GenerateAudioJob($request->id))->handle(app(AudioGenerationService::class));

        $this->assertSame(AudioGenerationRequestStatus::Success, $request->fresh()->status);

        $log = AiGeneration::firstWhere('operation', 'audio_generation');
        $dump = json_encode([$log->toArray(), MediaAsset::firstOrFail()->toArray()]);
        $this->assertStringNotContainsString($text, (string) $dump);
        $this->assertStringNotContainsString($wav, (string) $dump);
        $this->assertStringNotContainsString('test-auth-key', (string) $dump);
    }
}
