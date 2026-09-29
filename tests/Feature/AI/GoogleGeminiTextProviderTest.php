<?php

namespace Tests\Feature\AI;

use App\AI\Contracts\AiTextProvider;
use App\AI\Exceptions\AiProviderException;
use App\AI\Providers\GoogleGeminiTextProvider;
use App\Models\AiGeneration;
use App\Services\AiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleGeminiTextProviderTest extends TestCase
{
    use RefreshDatabase;

    private function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'status' => ['type' => 'string'],
                'message' => ['type' => 'string'],
            ],
            'required' => ['status', 'message'],
        ];
    }

    private function enable(array $overrides = []): void
    {
        config()->set('ai.google.enabled', true);
        config()->set('ai.google.auth_key', 'test-auth-key');
        config()->set('ai.google.model', 'gemini-3.8-flash');

        foreach ($overrides as $key => $value) {
            config()->set('ai.google.'.$key, $value);
        }
    }

    private function successPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 'interaction-123',
            'output_text' => '{"status":"ok","message":"conectado"}',
            'usage' => ['input_tokens' => 12, 'output_tokens' => 8],
        ], $overrides);
    }

    private function provider(): AiTextProvider
    {
        return app(AiTextProvider::class);
    }

    public function test_provider_resolve_para_google(): void
    {
        $this->assertInstanceOf(GoogleGeminiTextProvider::class, $this->provider());
    }

    public function test_provider_desabilitado(): void
    {
        config()->set('ai.google.enabled', false);

        try {
            $this->provider()->generateStructured('op', 'inst', 'in', $this->schema());
            $this->fail('Exceção esperada.');
        } catch (AiProviderException $e) {
            $this->assertSame('provider_disabled', $e->errorCode);
        }
    }

    public function test_credencial_ausente(): void
    {
        config()->set('ai.google.enabled', true);
        config()->set('ai.google.auth_key', '');

        try {
            $this->provider()->generateStructured('op', 'inst', 'in', $this->schema());
            $this->fail('Exceção esperada.');
        } catch (AiProviderException $e) {
            $this->assertSame('credentials_missing', $e->errorCode);
        }

        Http::assertNothingSent();
    }

    public function test_sucesso_estruturado(): void
    {
        $this->enable();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->successPayload(), 200)]);

        $result = $this->provider()->generateStructured('connection_test', 'inst', 'in', $this->schema());

        $this->assertSame('ok', $result->data['status']);
        $this->assertSame('google', $result->provider);
        $this->assertSame('gemini-3.8-flash', $result->model);
        $this->assertSame(12, $result->inputTokens);
        $this->assertSame(8, $result->outputTokens);
        $this->assertSame('interaction-123', $result->externalRequestId);

        Http::assertSent(function (Request $request) {
            return $request->hasHeader('x-goog-api-key', 'test-auth-key')
                && $request['model'] === 'gemini-3.8-flash'
                && $request['response_format']['mime_type'] === 'application/json';
        });
    }

    public function test_erro_401_sem_retry(): void
    {
        $this->enable();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'x'], 401)]);

        try {
            $this->provider()->generateStructured('op', 'inst', 'in', $this->schema());
            $this->fail('Exceção esperada.');
        } catch (AiProviderException $e) {
            $this->assertSame('unauthorized', $e->errorCode);
        }

        Http::assertSentCount(1);
    }

    public function test_erro_429_com_retry_limitado(): void
    {
        $this->enable();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'x'], 429)]);

        try {
            $this->provider()->generateStructured('op', 'inst', 'in', $this->schema());
            $this->fail('Exceção esperada.');
        } catch (AiProviderException $e) {
            $this->assertSame('rate_limited', $e->errorCode);
        }

        Http::assertSentCount(2);
    }

    public function test_429_seguido_de_sucesso(): void
    {
        $this->enable();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(['error' => 'x'], 429)
            ->push($this->successPayload(), 200),
        ]);

        $result = $this->provider()->generateStructured('op', 'inst', 'in', $this->schema());

        $this->assertSame('ok', $result->data['status']);
        Http::assertSentCount(2);
    }

    public function test_timeout(): void
    {
        $this->enable();
        Http::fake(function () {
            throw new ConnectionException('timeout');
        });

        try {
            $this->provider()->generateStructured('op', 'inst', 'in', $this->schema());
            $this->fail('Exceção esperada.');
        } catch (AiProviderException $e) {
            $this->assertSame('timeout', $e->errorCode);
        }
    }

    public function test_json_invalido(): void
    {
        $this->enable();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(
            ['id' => 'x', 'output_text' => 'não é json {'], 200
        )]);

        try {
            $this->provider()->generateStructured('op', 'inst', 'in', $this->schema());
            $this->fail('Exceção esperada.');
        } catch (AiProviderException $e) {
            $this->assertSame('invalid_json', $e->errorCode);
        }
    }

    public function test_schema_invalido_campo_obrigatorio_ausente(): void
    {
        $this->enable();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(
            ['id' => 'x', 'output_text' => '{"status":"ok"}'], 200
        )]);

        try {
            $this->provider()->generateStructured('op', 'inst', 'in', $this->schema());
            $this->fail('Exceção esperada.');
        } catch (AiProviderException $e) {
            $this->assertSame('schema_mismatch', $e->errorCode);
        }
    }

    public function test_mapeamento_5xx_preserva_http_exato(): void
    {
        $this->enable();

        // 500, 503 e 504 têm 1 retry interno; 501 não. Ordem de consumo:
        // 500,500,501,503,503,504,504.
        $expected = [
            ['api_error', 500],
            ['unimplemented', 501],
            ['service_unavailable', 503],
            ['deadline_exceeded', 504],
        ];

        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(['error' => ['message' => 'm']], 500)
            ->push(['error' => ['message' => 'm']], 500)
            ->push(['error' => ['message' => 'm']], 501)
            ->push(['error' => ['message' => 'm']], 503)
            ->push(['error' => ['message' => 'm']], 503)
            ->push(['error' => ['message' => 'm']], 504)
            ->push(['error' => ['message' => 'm']], 504),
        ]);

        foreach ($expected as [$code, $http]) {
            try {
                $this->provider()->generateStructured('op', 'inst', 'in', $this->schema());
                $this->fail('Exceção esperada.');
            } catch (AiProviderException $e) {
                $this->assertSame($code, $e->errorCode);
                $this->assertSame($http, $e->httpStatus);
                $this->assertSame($http, $e->details['http_status']);
            }
        }
    }

    public function test_mensagem_sanitizada_e_truncada(): void
    {
        $this->enable();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(
            ['error' => ['code' => 503, 'status' => 'UNAVAILABLE', 'message' => str_repeat('z', 500)]],
            503
        )]);

        try {
            $this->provider()->generateStructured('op', 'inst', 'in', $this->schema());
            $this->fail('Exceção esperada.');
        } catch (AiProviderException $e) {
            $this->assertSame('service_unavailable', $e->errorCode);
            $this->assertLessThanOrEqual(300, mb_strlen($e->getMessage()));
        }
    }

    public function test_nenhum_segredo_persistido_no_log(): void
    {
        $this->enable();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(
            ['error' => ['code' => 503, 'status' => 'UNAVAILABLE', 'message' => 'busy']],
            503
        )]);

        try {
            app(AiService::class)->testConnection();
            $this->fail('Exceção esperada.');
        } catch (AiProviderException $e) {
            // Esperado.
        }

        $log = AiGeneration::latest()->firstOrFail();
        $dump = json_encode([$log->error_code, $log->metadata]);
        $this->assertStringNotContainsString('test-auth-key', $dump);
        $this->assertArrayNotHasKey('input', (array) $log->metadata);
        $this->assertArrayNotHasKey('prompt', (array) $log->metadata);
    }
}
