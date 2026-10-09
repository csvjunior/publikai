<?php

namespace App\Services;

use App\AI\Contracts\AiVideoProvider;
use App\AI\Exceptions\AiProviderException;
use App\Enums\AiGenerationStatus;
use App\Enums\MediaAssetSource;
use App\Enums\MediaAssetStatus;
use App\Enums\MediaAssetType;
use App\Enums\VideoGenerationRequestStatus;
use App\Models\AiGeneration;
use App\Models\MediaAsset;
use App\Models\VideoGenerationRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Geração de vídeo image-to-video (Sprint 5.6.0, Omni Flash síncrono).
 * createRequest(): valida e enfileira (request web rápido).
 * process(): executado pelo Job — provider (resposta traz o vídeo),
 * inspeção FFprobe, MediaAsset video. Falhas nunca criam asset nem
 * deixam temp órfão. Sem prompt/binário em banco ou logs.
 */
class VideoGenerationService
{
    /**
     * @var string[]
     */
    private const RATIOS = ['16:9', '9:16'];

    /**
     * @var string[]
     */
    private const SOURCE_MIMES = ['image/jpeg', 'image/png'];

    public function __construct(
        protected AiVideoProvider $provider,
        protected VideoInspector $inspector,
    ) {}

    /**
     * @param  array{aspect_ratio?: ?string, duration_seconds?: ?int, content_script_id?: ?int, content_production_id?: ?int, source_media_asset_id?: ?int}  $options
     *
     * @throws ValidationException
     */
    public function createRequest(string $prompt, array $options, ?int $createdBy = null): VideoGenerationRequest
    {
        $config = config('ai.google.video');

        $options = [
            'aspect_ratio' => $options['aspect_ratio'] ?? $config['default_aspect_ratio'],
            'duration_seconds' => $options['duration_seconds'] ?? $config['default_duration'],
            'content_script_id' => $options['content_script_id'] ?? null,
            'content_production_id' => $options['content_production_id'] ?? null,
            'source_media_asset_id' => isset($options['source_media_asset_id']) ? (int) $options['source_media_asset_id'] : null,
        ];

        validator(
            ['prompt' => $prompt] + $options,
            [
                'prompt' => ['required', 'string', 'min:10', 'max:2000'],
                'aspect_ratio' => ['required', 'in:'.implode(',', self::RATIOS)],
                'duration_seconds' => ['required', 'integer', 'in:8'],
                'content_script_id' => ['nullable', 'integer', 'exists:content_scripts,id'],
                'content_production_id' => ['nullable', 'integer', 'exists:content_productions,id'],
                'source_media_asset_id' => ['required', 'integer', 'exists:media_assets,id'],
            ]
        )->validate();

        return VideoGenerationRequest::create([
            'status' => VideoGenerationRequestStatus::Pending,
            'prompt' => $prompt,
            'aspect_ratio' => $options['aspect_ratio'],
            'duration_seconds' => $options['duration_seconds'],
            'provider' => config('ai.provider', 'google'),
            'model' => (string) $config['model'],
            'content_script_id' => $options['content_script_id'],
            'content_production_id' => $options['content_production_id'],
            'source_media_asset_id' => $options['source_media_asset_id'],
            'created_by' => $createdBy,
        ]);
    }

    public function process(VideoGenerationRequest $request): void
    {
        $config = config('ai.google.video');

        $log = AiGeneration::create([
            'provider' => $request->provider ?? config('ai.provider', 'google'),
            'model' => (string) ($request->model ?? $config['model']),
            'operation' => 'video_generation',
            'status' => AiGenerationStatus::Pending,
        ]);

        $started = microtime(true);
        $tempPath = null;
        $finalPath = null;

        try {
            $request->update(['status' => VideoGenerationRequestStatus::Starting]);

            $source = $this->resolveSource($request);

            $request->update(['status' => VideoGenerationRequestStatus::Processing]);

            // Início efetivo do processamento (não criação do request).
            $request->update(['started_at' => now()]);

            $result = $this->provider->generate(
                $request->prompt,
                $source['binary'],
                $source['mime'],
                [
                    'aspect_ratio' => $request->aspect_ratio,
                    'duration_seconds' => $request->duration_seconds,
                ],
            );

            $tempPath = $this->tempPath();
            file_put_contents($tempPath, $result->videoData);

            $metadata = $this->inspector->inspect($tempPath);

            if ($metadata === null) {
                throw new AiProviderException('invalid_video', 'O provider não retornou um vídeo válido.');
            }

            $finalPath = $this->store($tempPath, $metadata->mimeType);

            $asset = MediaAsset::create([
                'type' => MediaAssetType::Video,
                'source' => MediaAssetSource::AiGenerated,
                'provider' => $request->provider ?? config('ai.provider', 'google'),
                'model' => (string) ($request->model ?? $config['model']),
                'disk' => 'public',
                'path' => $finalPath,
                'filename' => basename($finalPath),
                'mime_type' => $metadata->mimeType,
                'width' => $metadata->width,
                'height' => $metadata->height,
                'duration_seconds' => $metadata->durationSeconds !== null ? (int) round($metadata->durationSeconds) : $request->duration_seconds,
                'size_bytes' => $metadata->sizeBytes ?? filesize($finalPath) ?: null,
                'aspect_ratio' => $request->aspect_ratio,
                'status' => MediaAssetStatus::Ready,
                'parent_media_asset_id' => $request->source_media_asset_id,
                'created_by' => $request->created_by,
                'metadata' => [
                    'aspect_ratio' => $request->aspect_ratio,
                    'duration_seconds' => $request->duration_seconds,
                ],
            ]);

            $request->update([
                'status' => VideoGenerationRequestStatus::Success,
                'media_asset_id' => $asset->id,
                'completed_at' => now(),
            ]);

            $log->update([
                'status' => AiGenerationStatus::Success,
                'duration_ms' => $result->durationMs,
                'external_request_id' => $result->externalRequestId,
                'metadata' => [
                    'mime_type' => $metadata->mimeType,
                    'aspect_ratio' => $request->aspect_ratio,
                    'duration_seconds' => $request->duration_seconds,
                    'source_media_asset_id' => $request->source_media_asset_id,
                ],
            ]);
        } catch (AiProviderException $e) {
            $this->cleanup($tempPath);
            $this->cleanup($finalPath);

            $request->update([
                'status' => VideoGenerationRequestStatus::Failed,
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
     * Resolve a source do snapshot (Sprint 5.6.0). Arquivo ausente →
     * source_missing; binário inválido → source_invalid; MIME fora de
     * jpeg/png → source_unsupported (sem provider call, sem custo).
     *
     * @return array{binary: string, mime: string}
     *
     * @throws AiProviderException
     */
    protected function resolveSource(VideoGenerationRequest $request): array
    {
        $asset = MediaAsset::find($request->source_media_asset_id);

        if (! $asset || ! Storage::disk($asset->disk)->exists($asset->path)) {
            throw new AiProviderException('source_missing', 'A imagem base não está mais disponível.');
        }

        $binary = Storage::disk($asset->disk)->get($asset->path);
        $info = is_string($binary) ? @getimagesizefromstring($binary) : false;

        if ($binary === false || $info === false) {
            throw new AiProviderException('source_invalid', 'A imagem base não é mais válida.');
        }

        if (! in_array($info['mime'] ?? null, self::SOURCE_MIMES, true)) {
            throw new AiProviderException('source_unsupported', 'Formato da imagem base não suportado para vídeo.');
        }

        return ['binary' => $binary, 'mime' => $info['mime']];
    }

    protected function tempPath(): string
    {
        $dir = storage_path('app/tmp/video-generation');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir.'/'.Str::uuid().'.mp4';
    }

    protected function store(string $tempPath, string $mime): string
    {
        $ext = match ($mime) {
            'video/webm' => '.webm',
            'video/quicktime' => '.mov',
            default => '.mp4',
        };

        $path = 'videos/'.now()->format('Y/m').'/'.Str::uuid().$ext;

        Storage::disk('public')->put($path, file_get_contents($tempPath));
        @unlink($tempPath);

        return $path;
    }

    protected function cleanup(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'videos/') && is_file($path)) {
            @unlink($path);
        }

        if ($path && str_starts_with($path, 'videos/') && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
