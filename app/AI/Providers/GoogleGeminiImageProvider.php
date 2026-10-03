<?php

namespace App\AI\Providers;

use App\AI\AiImageReference;
use App\AI\Contracts\AiImageProvider;
use App\AI\Exceptions\AiCredentialsMissingException;
use App\AI\Exceptions\AiProviderDisabledException;
use App\AI\Exceptions\AiProviderException;
use App\AI\Results\AiImageGenerationResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Provider Google de imagem via Interactions API REST (Sprint 5.5.0,
 * Nano Banana 2 — gemini-3.1-flash-image; referência Sprint 5.5.2).
 *
 * Referências oficiais:
 * - https://ai.google.dev/gemini-api/docs/image-generation (text-to-image e
 *   text-and-image-to-image: input como blocos [{type:text},
 *   {type:image, mime_type, data(base64)}]; gemini-3.1-flash-image aceita até
 *   4 imagens de personagem para consistência).
 * - POST {base}/interactions, header x-goog-api-key (mesma auth key do texto).
 * - response_format {type: "image", mime_type?, aspect_ratio?, image_size?}.
 * - Resposta REST: steps[] → model_output → content[type=image] {data, mime_type}.
 *   (output_image é conveniência de SDK — suportado só como fallback.)
 *
 * Sem retry: 1 tentativa, sempre abaixo do teto PHP. Sem SDK.
 * base64 da referência existe só no payload HTTP em memória.
 */
class GoogleGeminiImageProvider implements AiImageProvider
{
    /**
     * @param  array{aspect_ratio?: string, image_size?: string, mime_type?: string}  $options
     * @param  list<AiImageReference>  $references
     */
    public function generate(string $prompt, array $options = [], array $references = []): AiImageGenerationResult
    {
        $config = config('ai.google.image');
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
                    'input' => $this->input($prompt, $references),
                    'response_format' => [
                        'type' => 'image',
                        'mime_type' => $options['mime_type'] ?? $config['default_mime_type'],
                        'aspect_ratio' => $options['aspect_ratio'] ?? $config['default_aspect_ratio'],
                        'image_size' => $options['image_size'] ?? $config['default_size'],
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw new AiProviderException('timeout', 'Tempo esgotado na geração da imagem.');
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
     * Sem referências: string simples (payload 5.5.0 inalterado). Com
     * referências: text + N image blocks no formato oficial (primary
     * primeiro — ordenação definida pelo chamador).
     *
     * @param  list<AiImageReference>  $references
     * @return string|array<int, array<string, string>>
     */
    protected function input(string $prompt, array $references): string|array
    {
        if ($references === []) {
            return $prompt;
        }

        $blocks = [['type' => 'text', 'text' => $prompt]];

        foreach ($references as $reference) {
            $blocks[] = [
                'type' => 'image',
                'mime_type' => $reference->mimeType,
                'data' => base64_encode($reference->binary),
            ];
        }

        return $blocks;
    }

    /**
     * Localiza o ÚLTIMO bloco de imagem válido (coerente com as convenience
     * properties dos SDKs): steps → model_output → content[type=image].
     * Ignora input/tool results. Fallback: output_image legado.
     *
     * @return array{data: string, mime_type: ?string}|null
     */
    protected function findImageBlock(mixed $json): ?array
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
                    && ($block['type'] ?? null) === 'image'
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

        if ($found !== null) {
            return $found;
        }

        $legacy = $json['output_image'] ?? null;
        $data = is_array($legacy) ? ($legacy['data'] ?? null) : null;

        if (! is_string($data) || trim($data) === '') {
            return null;
        }

        $mime = is_array($legacy) ? ($legacy['mime_type'] ?? null) : null;

        return ['data' => $data, 'mime_type' => is_string($mime) ? $mime : null];
    }

    /**
     * @throws AiProviderException
     */
    protected function parseSuccess(mixed $json, string $model, float $started): AiImageGenerationResult
    {
        $block = $this->findImageBlock($json);

        if ($block === null) {
            throw new AiProviderException('invalid_image', 'O provider não retornou imagem válida.');
        }

        $binary = base64_decode($block['data'], true);

        if ($binary === false || $binary === '') {
            throw new AiProviderException('invalid_image', 'O provider não retornou imagem válida.');
        }

        $info = @getimagesizefromstring($binary);

        if ($info === false) {
            throw new AiProviderException('invalid_image', 'O provider não retornou imagem válida.');
        }

        $mime = $info['mime'] ?? null;

        if (! in_array($mime, ['image/jpeg', 'image/png'], true)) {
            throw new AiProviderException('invalid_image', 'O provider não retornou imagem válida.');
        }

        return new AiImageGenerationResult(
            imageData: $binary,
            mimeType: $mime,
            provider: 'google',
            model: $model,
            width: $info[0] ?: null,
            height: $info[1] ?: null,
            externalRequestId: is_string($json['id'] ?? null) ? $json['id'] : null,
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
            : 'Falha na geração da imagem.';

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
