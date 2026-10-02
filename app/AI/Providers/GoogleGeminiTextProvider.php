<?php

namespace App\AI\Providers;

use App\AI\Contracts\AiTextProvider;
use App\AI\Exceptions\AiCredentialsMissingException;
use App\AI\Exceptions\AiProviderDisabledException;
use App\AI\Exceptions\AiProviderException;
use App\AI\Results\AiGenerationResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Provider Google Gemini via Interactions API REST (Sprint 5.0).
 *
 * Referência: https://ai.google.dev/gemini-api/docs (docs vigentes em 09/2026).
 * - POST {base_url}/interactions, header x-goog-api-key (auth key do AI Studio).
 * - Structured output via response_format {type, mime_type, schema}.
 * - Resposta REST: steps[] → model_output → content[type=text].text
 *   (output_text é conveniência de SDK — suportado só como fallback).
 * - Sem SDK: Laravel HTTP Client é suficiente.
 *
 * Retry conservador: 1 repetição apenas para 429/5xx. Nunca para
 * 401/403 ou erros de validação. Sem fila (validação síncrona).
 */
class GoogleGeminiTextProvider implements AiTextProvider
{
    public function generateStructured(string $operation, string $instructions, string $input, array $schema): AiGenerationResult
    {
        $config = config('ai.google');

        if (! ($config['enabled'] ?? false)) {
            throw new AiProviderDisabledException;
        }

        $authKey = (string) ($config['auth_key'] ?? '');

        if ($authKey === '') {
            throw new AiCredentialsMissingException;
        }

        $model = (string) $config['model'];
        $started = microtime(true);

        $payload = [
            'model' => $model,
            'system_instruction' => $instructions,
            'input' => $input,
            'response_format' => [
                'type' => 'text',
                'mime_type' => 'application/json',
                'schema' => $schema,
            ],
        ];

        $attempts = 0;
        $lastStatus = 0;
        $lastError = null;

        do {
            $attempts++;

            try {
                $response = Http::withHeaders(['x-goog-api-key' => $authKey])
                    ->withOptions(['connect_timeout' => (int) $config['connect_timeout']])
                    ->timeout((int) $config['timeout'])
                    ->post(rtrim((string) $config['base_url'], '/').'/interactions', $payload);
            } catch (ConnectionException $e) {
                throw new AiProviderException('timeout', 'Tempo esgotado na chamada ao provider de IA.');
            }

            $lastStatus = $response->status();

            if ($response->successful()) {
                return $this->parseSuccess($response->json(), $schema, $model, $started);
            }

            $lastError = $this->sanitizedError($response->json(), $lastStatus);

            if (! in_array($lastStatus, [429, 500, 502, 503, 504], true) || $attempts >= 2) {
                break;
            }
        } while (true);

        throw new AiProviderException(
            $this->errorCodeFor($lastStatus),
            $lastError['message'] ?? 'Falha na geração de conteúdo por IA.',
            $lastStatus,
            $lastError['details'],
        );
    }

    /**
     * Extrai só o diagnóstico seguro do corpo de erro do Google:
     * HTTP status + error.code/error.status + mensagem truncada.
     * Nunca headers, prompt, body completo ou segredos.
     *
     * @return array{message: string, details: array<string, scalar|null>}
     */
    protected function sanitizedError(mixed $json, int $httpStatus): array
    {
        $error = is_array($json) ? ($json['error'] ?? []) : [];
        $error = is_array($error) ? $error : [];

        $message = $error['message'] ?? null;
        $message = is_string($message) && trim($message) !== ''
            ? mb_substr(trim($message), 0, 300)
            : 'Falha na geração de conteúdo por IA.';

        return [
            'message' => $message,
            'details' => [
                'http_status' => $httpStatus,
                'google_code' => is_scalar($error['code'] ?? null) ? $error['code'] : null,
                'google_status' => is_string($error['status'] ?? null) ? $error['status'] : null,
            ],
        ];
    }

    /**
     * Extrai o texto concatenando blocos type=text de model_output
     * (steps[] → content[]). Fallback: output_text legado.
     */
    protected function extractText(mixed $json): ?string
    {
        if (! is_array($json)) {
            return null;
        }

        $texts = [];

        foreach ((array) ($json['steps'] ?? []) as $step) {
            if (! is_array($step) || ($step['type'] ?? null) !== 'model_output') {
                continue;
            }

            $content = $step['content'] ?? null;
            $blocks = is_array($content) && array_is_list($content) ? $content : [$content];

            foreach ($blocks as $block) {
                if (is_array($block)
                    && ($block['type'] ?? null) === 'text'
                    && is_string($block['text'] ?? null)
                    && trim($block['text']) !== ''
                ) {
                    $texts[] = $block['text'];
                }
            }
        }

        if ($texts !== []) {
            return implode('', $texts);
        }

        $legacy = $json['output_text'] ?? null;

        return is_string($legacy) && trim($legacy) !== '' ? $legacy : null;
    }

    /**
     * @param  array<string, mixed>  $schema
     *
     * @throws AiProviderException
     */
    protected function parseSuccess(mixed $json, array $schema, string $model, float $started): AiGenerationResult
    {
        if (! is_array($json)) {
            throw new AiProviderException('invalid_response', 'Resposta inválida do provider de IA.');
        }

        $text = $this->extractText($json);

        if ($text === null) {
            throw new AiProviderException('invalid_response', 'Resposta inválida do provider de IA.');
        }

        $data = json_decode($text, true);

        if (! is_array($data)) {
            throw new AiProviderException('invalid_json', 'Resposta do provider de IA fora do formato esperado.');
        }

        foreach ((array) ($schema['required'] ?? []) as $field) {
            if (! array_key_exists($field, $data)) {
                throw new AiProviderException('schema_mismatch', 'Resposta do provider de IA fora do formato esperado.');
            }
        }

        return new AiGenerationResult(
            data: $data,
            provider: 'google',
            model: $model,
            inputTokens: $this->extractTokens($json, ['promptTokenCount', 'prompt_token_count', 'inputTokens', 'input_tokens']),
            outputTokens: $this->extractTokens($json, ['candidatesTokenCount', 'candidates_token_count', 'outputTokens', 'output_tokens']),
            externalRequestId: is_string($json['id'] ?? null) ? $json['id'] : null,
            durationMs: (int) ((microtime(true) - $started) * 1000),
        );
    }

    /**
     * @param  array<string, mixed>  $json
     * @param  string[]  $candidates
     */
    protected function extractTokens(array $json, array $candidates): ?int
    {
        $pools = [$json, (array) ($json['usage'] ?? []), (array) ($json['usage_metadata'] ?? [])];

        foreach ($pools as $pool) {
            foreach ($candidates as $key) {
                if (isset($pool[$key]) && is_numeric($pool[$key])) {
                    return (int) $pool[$key];
                }
            }
        }

        return null;
    }

    protected function errorCodeFor(int $status): string
    {
        return match (true) {
            $status === 401 || $status === 403 => 'unauthorized',
            $status === 429 => 'rate_limited',
            $status === 500 => 'api_error',
            $status === 501 => 'unimplemented',
            $status === 503 => 'service_unavailable',
            $status === 504 => 'deadline_exceeded',
            $status >= 500 => 'server_error',
            default => 'request_failed',
        };
    }
}
