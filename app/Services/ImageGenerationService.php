<?php

namespace App\Services;

use App\AI\Contracts\AiImageProvider;
use App\AI\Exceptions\AiProviderException;
use App\Enums\AiGenerationStatus;
use App\Enums\ImageGenerationRequestStatus;
use App\Enums\MediaAssetSource;
use App\Enums\MediaAssetStatus;
use App\Enums\MediaAssetType;
use App\Models\AiGeneration;
use App\Models\ImageGenerationRequest;
use App\Models\MediaAsset;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Geração de imagem assíncrona (Sprint 5.5.0 async).
 * createRequest(): valida e enfileira (request web rápido).
 * process(): executado pelo Job — provider, binário, Storage, MediaAsset,
 * ai_generations. Falhas limpam parcial e nunca criam asset.
 * Sem prompt/base64 no banco fora do request, sem segredos em logs.
 */
class ImageGenerationService
{
    /**
     * @var string[]
     */
    private const RATIOS = ['1:1', '4:5', '9:16', '16:9'];

    /**
     * @var string[]
     */
    private const SIZES = ['1K'];

    /**
     * @var string[]
     */
    private const MIMES = ['image/jpeg', 'image/png'];

    public function __construct(protected AiImageProvider $provider) {}

    /**
     * @param  array{aspect_ratio?: ?string, image_size?: ?string, mime_type?: ?string}  $options
     *
     * @throws ValidationException
     */
    public function createRequest(string $prompt, array $options, ?int $createdBy = null): ImageGenerationRequest
    {
        $config = config('ai.google.image');

        $options = [
            'aspect_ratio' => $options['aspect_ratio'] ?? $config['default_aspect_ratio'],
            'image_size' => $options['image_size'] ?? $config['default_size'],
            'mime_type' => $options['mime_type'] ?? $config['default_mime_type'],
        ];

        validator(
            ['prompt' => $prompt] + $options,
            [
                'prompt' => ['required', 'string', 'min:10', 'max:2000'],
                'aspect_ratio' => ['required', 'in:'.implode(',', self::RATIOS)],
                'image_size' => ['required', 'in:'.implode(',', self::SIZES)],
                'mime_type' => ['required', 'in:'.implode(',', self::MIMES)],
            ]
        )->validate();

        return ImageGenerationRequest::create([
            'status' => ImageGenerationRequestStatus::Pending,
            'prompt' => $prompt,
            'aspect_ratio' => $options['aspect_ratio'],
            'image_size' => $options['image_size'],
            'mime_type' => $options['mime_type'],
            'provider' => config('ai.provider', 'google'),
            'model' => (string) $config['model'],
            'created_by' => $createdBy,
        ]);
    }

    public function process(ImageGenerationRequest $request): void
    {
        $config = config('ai.google.image');

        $log = AiGeneration::create([
            'provider' => $request->provider ?? config('ai.provider', 'google'),
            'model' => (string) ($request->model ?? $config['model']),
            'operation' => 'image_generation',
            'status' => AiGenerationStatus::Pending,
        ]);

        $started = microtime(true);
        $path = null;

        try {
            $result = $this->provider->generate($request->prompt, [
                'aspect_ratio' => $request->aspect_ratio,
                'image_size' => $request->image_size,
                'mime_type' => $request->mime_type,
            ]);

            $path = $this->store($result->imageData, $result->mimeType);

            $asset = MediaAsset::create([
                'type' => MediaAssetType::Image,
                'source' => MediaAssetSource::AiGenerated,
                'provider' => $result->provider,
                'model' => $result->model,
                'disk' => 'public',
                'path' => $path,
                'filename' => basename($path),
                'mime_type' => $result->mimeType,
                'width' => $result->width,
                'height' => $result->height,
                'size_bytes' => $result->sizeBytes(),
                'aspect_ratio' => $request->aspect_ratio,
                'status' => MediaAssetStatus::Ready,
                'created_by' => $request->created_by,
                'metadata' => [
                    'mime_type' => $request->mime_type,
                    'image_size' => $request->image_size,
                ],
            ]);

            $request->update([
                'status' => ImageGenerationRequestStatus::Success,
                'media_asset_id' => $asset->id,
                'completed_at' => now(),
            ]);

            $log->update([
                'status' => AiGenerationStatus::Success,
                'duration_ms' => $result->durationMs,
                'external_request_id' => $result->externalRequestId,
                'metadata' => [
                    'mime_type' => $result->mimeType,
                    'aspect_ratio' => $request->aspect_ratio,
                    'image_size' => $request->image_size,
                ],
            ]);
        } catch (AiProviderException $e) {
            $this->cleanupPartial($path);

            $request->update([
                'status' => ImageGenerationRequestStatus::Failed,
                'error_code' => $e->errorCode,
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            $log->update([
                'status' => AiGenerationStatus::Failed,
                'error_code' => $e->errorCode,
                'metadata' => $e->details,
                'duration_ms' => (int) ((microtime(true) - $started) * 1000),
            ]);
        }
    }

    protected function store(string $binary, string $mime): string
    {
        $path = 'images/'.now()->format('Y/m').'/'.Str::uuid().($mime === 'image/png' ? '.png' : '.jpg');

        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    protected function cleanupPartial(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
