<?php

namespace App\Services;

use App\AI\AiImageReference;
use App\AI\AiImageSource;
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
     * @param  array{aspect_ratio?: ?string, image_size?: ?string, mime_type?: ?string, content_script_id?: ?int, content_production_id?: ?int, source_media_asset_id?: ?int, reference_media_asset_ids?: ?int[], purpose?: ?string, is_primary?: bool}  $options
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
            'content_production_id' => $options['content_production_id'] ?? null,
            'source_media_asset_id' => isset($options['source_media_asset_id']) ? (int) $options['source_media_asset_id'] : null,
            'reference_media_asset_ids' => array_values(array_unique(array_map(
                'intval',
                (array) ($options['reference_media_asset_ids'] ?? [])
            ))),
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
                'content_production_id' => ['nullable', 'integer', 'exists:content_productions,id'],
                'source_media_asset_id' => ['nullable', 'integer', 'exists:media_assets,id'],
                'reference_media_asset_ids' => ['nullable', 'array', 'max:'.$this->maxReferences()],
                'reference_media_asset_ids.*' => ['integer', 'exists:media_assets,id'],
                'purpose' => ['required', 'in:cover,scene,product,background,other'],
                'is_primary' => ['boolean'],
            ]
        )->validate();

        return DB::transaction(function () use ($options, $prompt, $config, $createdBy) {
            $request = ImageGenerationRequest::create([
                'status' => ImageGenerationRequestStatus::Pending,
                'prompt' => $prompt,
                'aspect_ratio' => $options['aspect_ratio'],
                'image_size' => $options['image_size'],
                'mime_type' => $options['mime_type'],
                'provider' => config('ai.provider', 'google'),
                'model' => (string) $config['model'],
                'content_script_id' => $options['content_script_id'],
                'content_production_id' => $options['content_production_id'],
                'source_media_asset_id' => $options['source_media_asset_id'],
                'purpose' => $options['purpose'],
                'is_primary' => $options['is_primary'],
                'created_by' => $createdBy,
            ]);

            foreach ($options['reference_media_asset_ids'] as $position => $mediaAssetId) {
                $request->referenceImages()->attach($mediaAssetId, ['position' => $position + 1]);
            }

            return $request;
        });
    }

    public function maxReferences(): int
    {
        return max(1, (int) config('ai.google.image.max_references', 4));
    }

    public function process(ImageGenerationRequest $request): void
    {
        $config = config('ai.google.image');
        $isEdit = $request->source_media_asset_id !== null;

        $log = AiGeneration::create([
            'provider' => $request->provider ?? config('ai.provider', 'google'),
            'model' => (string) ($request->model ?? $config['model']),
            'operation' => $isEdit ? 'image_edit' : 'image_generation',
            'status' => AiGenerationStatus::Pending,
        ]);

        $started = microtime(true);
        $path = null;

        try {
            $source = $this->resolveSource($request);
            $references = $this->resolveReferences($request);

            $result = $this->provider->generate($request->prompt, [
                'aspect_ratio' => $request->aspect_ratio,
                'image_size' => $request->image_size,
                'mime_type' => $request->mime_type,
            ], $references, $source);

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
                'parent_media_asset_id' => $request->source_media_asset_id,
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
                    'source_media_asset_id' => $request->source_media_asset_id,
                    'reference_used' => $request->referenceImages()->exists(),
                    'reference_count' => $request->referenceImages()->count(),
                    'reference_media_asset_ids' => $request->referenceImages()->pluck('media_assets.id')->all(),
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

    /**
     * Resolve a source do snapshot do request (Sprint 5.5.4).
     * Sem source: fluxo image_generation atual. Arquivo ausente →
     * source_missing; binário inválido → source_invalid (sem provider call).
     *
     * @throws AiProviderException
     */
    protected function resolveSource(ImageGenerationRequest $request): ?AiImageSource
    {
        if ($request->source_media_asset_id === null) {
            return null;
        }

        $asset = MediaAsset::find($request->source_media_asset_id);

        if (! $asset || ! Storage::disk($asset->disk)->exists($asset->path)) {
            throw new AiProviderException(
                'source_missing',
                'A imagem base não está mais disponível.'
            );
        }

        $binary = Storage::disk($asset->disk)->get($asset->path);
        $info = is_string($binary) ? @getimagesizefromstring($binary) : false;

        if ($binary === false || $info === false
            || ! in_array($info['mime'] ?? null, ['image/jpeg', 'image/png'], true)
        ) {
            throw new AiProviderException(
                'source_invalid',
                'A imagem base não é mais válida.'
            );
        }

        return new AiImageSource(
            binary: $binary,
            mimeType: $info['mime'],
            width: $info[0] ?: null,
            height: $info[1] ?: null,
        );
    }

    /**
     * Resolve as referências do snapshot do request (Sprint 5.5.3).
     * Sem snapshot: fluxo textual atual. Qualquer arquivo ausente →
     * reference_missing; qualquer binário inválido → reference_invalid
     * (sem provider call, sem custo).
     *
     * @return AiImageReference[]
     *
     * @throws AiProviderException
     */
    protected function resolveReferences(ImageGenerationRequest $request): array
    {
        $references = [];

        foreach ($request->referenceImages()->get() as $asset) {
            if (! Storage::disk($asset->disk)->exists($asset->path)) {
                throw new AiProviderException(
                    'reference_missing',
                    'Uma das imagens de referência não está mais disponível.'
                );
            }

            $binary = Storage::disk($asset->disk)->get($asset->path);
            $info = is_string($binary) ? @getimagesizefromstring($binary) : false;

            if ($binary === false || $info === false
                || ! in_array($info['mime'] ?? null, ['image/jpeg', 'image/png'], true)
            ) {
                throw new AiProviderException(
                    'reference_invalid',
                    'Uma das imagens de referência não é mais válida.'
                );
            }

            $references[] = new AiImageReference(
                binary: $binary,
                mimeType: $info['mime'],
                width: $info[0] ?: null,
                height: $info[1] ?: null,
            );
        }

        return $references;
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
