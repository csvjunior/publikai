<?php

namespace App\Services;

use App\AI\Contracts\AiImageProvider;
use App\AI\Exceptions\AiProviderException;
use App\Enums\AiGenerationStatus;
use App\Enums\ContentScriptAssetPurpose;
use App\Enums\ImageGenerationRequestStatus;
use App\Enums\MediaAssetSource;
use App\Enums\MediaAssetStatus;
use App\Enums\MediaAssetType;
use App\Models\AiGeneration;
use App\Models\ContentScript;
use App\Models\ImageGenerationRequest;
use App\Models\MediaAsset;
use Illuminate\Support\Facades\DB;
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
     * @param  array{aspect_ratio?: ?string, image_size?: ?string, mime_type?: ?string, content_script_id?: ?int, purpose?: ?string, is_primary?: bool}  $options
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
            'content_script_id' => $options['content_script_id'] ?? null,
            'purpose' => $options['purpose'] ?? ContentScriptAssetPurpose::Scene->value,
            'is_primary' => (bool) ($options['is_primary'] ?? false),
        ];

        validator(
            ['prompt' => $prompt] + $options,
            [
                'prompt' => ['required', 'string', 'min:10', 'max:2000'],
                'aspect_ratio' => ['required', 'in:'.implode(',', self::RATIOS)],
                'image_size' => ['required', 'in:'.implode(',', self::SIZES)],
                'mime_type' => ['required', 'in:'.implode(',', self::MIMES)],
                'content_script_id' => ['nullable', 'integer', 'exists:content_scripts,id'],
                'purpose' => ['required', 'in:cover,scene,product,background,other'],
                'is_primary' => ['boolean'],
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
            'content_script_id' => $options['content_script_id'],
            'purpose' => $options['purpose'],
            'is_primary' => $options['is_primary'],
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

            $this->attachToScript($request, $asset);

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

    /**
     * Define o asset como principal do roteiro (transação).
     */
    public function markPrimary(ContentScript $script, MediaAsset $asset): void
    {
        abort_unless(
            $script->mediaAssets()->whereKey($asset->id)->exists(),
            404
        );

        DB::transaction(function () use ($script, $asset) {
            DB::table('content_script_media_assets')
                ->where('content_script_id', $script->id)
                ->update(['is_primary' => false]);

            $script->mediaAssets()->updateExistingPivot($asset->id, ['is_primary' => true]);
        });
    }

    protected function store(string $binary, string $mime): string
    {
        $path = 'images/'.now()->format('Y/m').'/'.Str::uuid().($mime === 'image/png' ? '.png' : '.jpg');

        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    /**
     * Vincula o asset ao roteiro (transação; primary desmarca anterior).
     * Sem request contextual, nada a vincular.
     */
    protected function attachToScript(ImageGenerationRequest $request, MediaAsset $asset): void
    {
        if (! $request->content_script_id) {
            return;
        }

        DB::transaction(function () use ($request, $asset) {
            if ($request->is_primary) {
                DB::table('content_script_media_assets')
                    ->where('content_script_id', $request->content_script_id)
                    ->update(['is_primary' => false]);
            }

            DB::table('content_script_media_assets')->updateOrInsert(
                [
                    'content_script_id' => $request->content_script_id,
                    'media_asset_id' => $asset->id,
                ],
                [
                    'purpose' => $request->purpose ?? ContentScriptAssetPurpose::Scene->value,
                    'is_primary' => (bool) $request->is_primary,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        });
    }

    protected function cleanupPartial(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
