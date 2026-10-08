<?php

namespace App\AI\Providers;

use App\AI\AiAudioGenerationInput;
use App\AI\Contracts\AiAudioProvider;
use App\AI\Exceptions\AiCredentialsMissingException;
use App\AI\Exceptions\AiProviderDisabledException;
use App\AI\Exceptions\AiProviderException;
use App\AI\Results\AiAudioGenerationResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Provider Google de TTS via Interactions API REST (Sprint 5.6.2).
 *
 * Referência oficial: https://ai.google.dev/gemini-api/docs/speech-generation
 * - Modelo `gemini-3.8-flash-tts` (single-speaker).
 * - POST {base}/interactions, header x-goog-api-key (mesma auth key).
 * - input: [{type: user_input, content: [{type: text, text,
 *   annotations: [{type: speech_metadata, style}]}]}].
 * - response_format: {type: "audio"} → WAV `audio/wav` com RIFF header
 *   (salvar bytes direto em .wav). generation_config: {speech_config:
 *   [{voice}]} (somente prebuilt oficiais; sem design/replication).
 * - Resposta REST: steps[] → model_output → content[type=audio]
 *   {data, mime_type} (output_audio é conveniência de SDK — fallback).
 *
 * Sem SDK. base64/texto só em memória/request — nunca em banco/logs.
 */
class GoogleGeminiAudioProvider implements AiAudioProvider
{
    public function generate(AiAudioGenerationInput $input): AiAudioGenerationResult
    {
        $config = config('ai.google.audio');
        $authKey = (string) config('ai.google.auth_key', '');

        if (! ($config['enabled'] ?? false)) {
            throw new AiProviderDisabledException;
        }

        if ($authKey === '') {
            throw new AiCredentialsMissingException;
        }

        $model = (string) $config['model'];
        $started = microtime(true);

        $textBlock = ['type' => 'text', 'text' => $input->text];

        if ($input->style !== null && trim($input->style) !== '') {
            $textBlock['annotations'] = [
                ['type' => 'speech_metadata', 'style' => $input->style],
            ];
        }

        try {
            $response = Http::withHeaders(['x-goog-api-key' => $authKey])
                ->withOptions(['connect_timeout' => (int) $config['connect_timeout']])
                ->timeout((int) $config['timeout'])
                ->post(rtrim((string) config('ai.google.base_url'), '/').'/interactions', [
                    'model' => $model,
                    'input' => [
                        ['type' => 'user_input', 'content' => [$textBlock]],
                    ],
                    'response_format' => ['type' => 'audio'],
                    'generation_config' => [
                        'speech_config' => [['voice' => $input->voice]],
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw new AiProviderException('timeout', 'Tempo esgotado na geração da narração.');
        }

        if (! $response->successful()) {
            $status = $response->status();
            $error = $this->sanitizedError($response->json(), $status);

            throw new AiProviderException(
                $this->errorCodeFor($status),
                $error['message'],
                $status,
                $error['details'],
            );
        }

        return $this->parseSuccess($response->json(), $model, $started);
    }

    /**
     * Localiza o ÚLTIMO bloco de áudio válido (mesmo padrão dos demais
     * parsers): steps → model_output → content[type=audio].
     *
     * @return array{data: string, mime_type: ?string}|null
     */
    protected function findAudioBlock(mixed $json): ?array
    {
        if (! is_array($json)) {
            return null;
        }

        $found = null;

        foreach ((array) ($json['steps'] ?? []) as $step) {
            if (! is_array($step) || ($step['type'] ?? null) !== 'model_output') {
                continue;
            }

            $content = $step['content'] ?? null;
            $blocks = is_array($content) && array_is_list($content) ? $content : [$content];

            foreach ($blocks as $block) {
                if (is_array($block)
                    && ($block['type'] ?? null) === 'audio'
                    && is_string($block['data'] ?? null)
                    && trim($block['data']) !== ''
                ) {
                    $mime = $block['mime_type'] ?? null;
                    $found = [
                        'data' => $block['data'],
                        'mime_type' => is_string($mime) ? $mime : null,
                    ];
                }
            }
        }

        return $found;
    }

    /**
     * @throws AiProviderException
     */
    protected function parseSuccess(mixed $json, string $model, float $started): AiAudioGenerationResult
    {
        $block = $this->findAudioBlock($json);

        if ($block === null) {
            throw new AiProviderException('invalid_audio', 'O provider não retornou um áudio válido.');
        }

        $binary = base64_decode($block['data'], true);

        if ($binary === false || $binary === '') {
            throw new AiProviderException('invalid_audio', 'O provider não retornou um áudio válido.');
        }

        $mime = $block['mime_type'] ?? 'audio/wav';

        if (! in_array($mime, ['audio/wav', 'audio/l16'], true)) {
            throw new AiProviderException('invalid_audio', 'O provider não retornou um áudio válido.');
        }

        $id = is_array($json) && is_string($json['id'] ?? null) ? $json['id'] : null;

        return new AiAudioGenerationResult(
            audioData: $binary,
            mimeType: $mime,
            provider: 'google',
            model: $model,
            externalRequestId: $id,
            durationMs: (int) ((microtime(true) - $started) * 1000),
        );
    }

    protected function sanitizedError(mixed $json, int $httpStatus): array
    {
        $error = is_array($json) ? ($json['error'] ?? []) : [];
        $error = is_array($error) ? $error : [];

        $message = $error['message'] ?? null;
        $message = is_string($message) && trim($message) !== ''
            ? mb_substr(trim($message), 0, 300)
            : 'Falha na geração da narração.';

        return [
            'message' => $message,
            'details' => [
                'http_status' => $httpStatus,
                'google_code' => is_scalar($error['code'] ?? null) ? $error['code'] : null,
                'google_status' => is_string($error['status'] ?? null) ? $error['status'] : null,
            ],
        ];
    }

    protected function errorCodeFor(int $status): string
    {
        return match (true) {
            $status === 400 => 'invalid_request',
            $status === 401 || $status === 403 => 'unauthorized',
            $status === 429 => 'rate_limited',
            $status === 503 => 'service_unavailable',
            $status >= 500 => 'server_error',
            default => 'request_failed',
        };
    }
}
