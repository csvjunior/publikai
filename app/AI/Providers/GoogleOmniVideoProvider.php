<?php

namespace App\AI\Providers;

use App\AI\Contracts\AiVideoProvider;
use App\AI\Exceptions\AiCredentialsMissingException;
use App\AI\Exceptions\AiProviderDisabledException;
use App\AI\Exceptions\AiProviderException;
use App\AI\Results\AiVideoGenerationResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Provider de vídeo via Interactions API REST (Sprint 5.6.0, Omni Flash).
 *
 * Referência oficial: https://ai.google.dev/gemini-api/docs/omni
 * (image-to-video + task=image_to_video; first/last frame; subjects).
 * - POST {base}/interactions, header x-goog-api-key (mesma auth key).
 * - input: [{type:image, data(base64), mime_type}, {type:text, text}].
 * - response_format: {type: "video", aspect_ratio: "9:16"} (720p default).
 * - Resposta REST: steps[] → model_output → content[type=video]
 *   {data, mime_type} (output_video é conveniência de SDK — não usar).
 * - Chamada síncrona (sem operação/polling); Job/queue dão o async.
 *
 * Sem SDK. base64 só em memória/request — nunca em banco/logs.
 */
class GoogleOmniVideoProvider implements AiVideoProvider
{
    /**
     * @var string[]
     */
    private const ALLOWED_MIMES = ['video/mp4', 'video/webm', 'video/quicktime'];

    /**
     * @param  array{aspect_ratio?: string, duration_seconds?: int}  $options
     */
    public function generate(string $prompt, string $imageBinary, string $imageMime, array $options = []): AiVideoGenerationResult
    {
        $config = config('ai.google.video');
        $authKey = (string) config('ai.google.auth_key', '');

        if (! ($config['enabled'] ?? false)) {
            throw new AiProviderDisabledException;
        }

        if ($authKey === '') {
            throw new AiCredentialsMissingException;
        }

        $model = (string) $config['model'];
        $started = microtime(true);

        try {
            $response = Http::withHeaders(['x-goog-api-key' => $authKey])
                ->withOptions(['connect_timeout' => (int) $config['connect_timeout']])
                ->timeout((int) $config['timeout'])
                ->post(rtrim((string) config('ai.google.base_url'), '/').'/interactions', [
                    'model' => $model,
                    'input' => [
                        [
                            'type' => 'image',
                            'data' => base64_encode($imageBinary),
                            'mime_type' => $imageMime,
                        ],
                        ['type' => 'text', 'text' => $prompt],
                    ],
                    'generation_config' => [
                        'video_config' => [
                            'task' => 'image_to_video',
                        ],
                    ],
                    'response_format' => [
                        'type' => 'video',
                        'aspect_ratio' => $options['aspect_ratio'] ?? $config['default_aspect_ratio'],
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw new AiProviderException('timeout', 'Tempo esgotado na geração do vídeo.');
        }

        if (! $response->successful()) {
            $status = $response->status();
            $error = $this->sanitizedError($response->json(), $status);

            throw new AiProviderException(
                $this->errorCodeFor($status, $error),
                $error['message'],
                $status,
                $error['details'],
            );
        }

        return $this->parseSuccess($response->json(), $model, $started);
    }

    /**
     * Localiza o ÚLTIMO bloco de vídeo válido (mesmo padrão dos parsers de
     * texto/imagem): steps → model_output → content[type=video].
     *
     * @return array{data: string, mime_type: ?string}|null
     */
    protected function findVideoBlock(mixed $json): ?array
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
                    && ($block['type'] ?? null) === 'video'
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
    protected function parseSuccess(mixed $json, string $model, float $started): AiVideoGenerationResult
    {
        $config = config('ai.google.video');
        $block = $this->findVideoBlock($json);

        if ($block === null) {
            throw new AiProviderException('invalid_video', 'O provider não retornou um vídeo válido.');
        }

        $binary = base64_decode($block['data'], true);
        $maxBytes = (int) ($config['max_download_bytes'] ?? 104857600);

        if ($binary === false || $binary === ''
            || strlen($binary) > $maxBytes
        ) {
            throw new AiProviderException('invalid_video', 'O provider não retornou um vídeo válido.');
        }

        $mime = $block['mime_type'] ?? 'video/mp4';

        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new AiProviderException('invalid_video', 'O provider não retornou um vídeo válido.');
        }

        $id = is_array($json) && is_string($json['id'] ?? null) ? $json['id'] : null;

        return new AiVideoGenerationResult(
            videoData: $binary,
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
            : 'Falha na geração do vídeo.';

        return [
            'message' => $message,
            'details' => [
                'http_status' => $httpStatus,
                'google_code' => is_scalar($error['code'] ?? null) ? $error['code'] : null,
                'google_status' => is_string($error['status'] ?? null) ? $error['status'] : null,
            ],
        ];
    }

    protected function errorCodeFor(int $status, array $error): string
    {
        if ($status === 400) {
            return 'invalid_request';
        }

        return match (true) {
            $status === 401 || $status === 403 => 'unauthorized',
            $status === 429 => 'rate_limited',
            $status === 503 => 'service_unavailable',
            $status >= 500 => 'server_error',
            default => 'request_failed',
        };
    }
}
