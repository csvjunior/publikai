<?php

namespace App\Services;

use App\Enums\MediaAssetSource;
use App\Enums\MediaAssetStatus;
use App\Enums\MediaAssetType;
use App\Enums\VideoCompositionRequestStatus;
use App\Models\ContentScript;
use App\Models\MediaAsset;
use App\Models\VideoCompositionInput;
use App\Models\VideoCompositionRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Composição local de vídeo (Sprint 5.6.1, FFmpeg).
 * createRequest(): valida snapshot (assets, trims, durações) e enfileira.
 * process(): executado pelo Job — normaliza, concatena, valida output,
 * MediaAsset video/composed. Temp sempre limpo. Sem shell cru.
 */
class VideoCompositionService
{
    public function __construct(
        protected VideoComposer $composer,
        protected VideoInspector $inspector,
    ) {}

    /**
     * @param  list<array{media_asset_id: int, position: int, trim_start_s?: ?float, trim_end_s?: ?float, image_duration_s?: ?float}>  $items
     *
     * @throws ValidationException
     */
    public function createRequest(int $contentScriptId, array $items, ?int $createdBy = null): VideoCompositionRequest
    {
        $config = config('video-composition');
        $script = ContentScript::findOrFail($contentScriptId);

        $max = (int) $config['max_inputs'];

        if ($items === [] || count($items) > $max) {
            throw ValidationException::withMessages(['assets' => 'Selecione de 1 a '.$max.' itens.']);
        }

        $positions = array_map(fn ($i) => (int) ($i['position'] ?? 0), $items);

        if (min($positions) < 1 || count(array_unique($positions)) !== count($positions)) {
            throw ValidationException::withMessages(['assets' => 'Posições inválidas ou duplicadas.']);
        }

        usort($items, fn ($a, $b) => (int) $a['position'] <=> (int) $b['position']);

        $totalMs = 0;
        $validated = [];

        foreach ($items as $item) {
            $asset = $script->mediaAssets()->whereKey((int) $item['media_asset_id'])->first();

            abort_unless($asset, 404);
            abort_unless(
                in_array($asset->type, [MediaAssetType::Image, MediaAssetType::Video], true)
                    && $asset->status === MediaAssetStatus::Ready,
                422,
                'Item inválido para composição.'
            );

            if ($asset->type === MediaAssetType::Video) {
                $durationMs = $this->videoDurationMs($asset);
                $startMs = isset($item['trim_start_s']) ? (int) round((float) $item['trim_start_s'] * 1000) : 0;
                $endMs = isset($item['trim_end_s']) ? (int) round((float) $item['trim_end_s'] * 1000) : $durationMs;

                if ($startMs < 0 || $endMs <= $startMs || $endMs > $durationMs) {
                    throw ValidationException::withMessages(['assets' => 'Corte inválido para o vídeo #'.$asset->id.'.']);
                }

                $totalMs += $endMs - $startMs;
                $validated[] = [
                    'media_asset_id' => $asset->id,
                    'position' => (int) $item['position'],
                    'trim_start_ms' => $startMs,
                    'trim_end_ms' => $endMs,
                    'image_duration_ms' => null,
                ];
            } else {
                $this->assertImage($asset);

                $durationMs = isset($item['image_duration_s'])
                    ? (int) round((float) $item['image_duration_s'] * 1000)
                    : (int) $config['image_default_duration_ms'];

                if ($durationMs < (int) $config['image_min_duration_ms']
                    || $durationMs > (int) $config['image_max_duration_ms']
                ) {
                    throw ValidationException::withMessages(['assets' => 'Duração inválida para a imagem #'.$asset->id.'.']);
                }

                $totalMs += $durationMs;
                $validated[] = [
                    'media_asset_id' => $asset->id,
                    'position' => (int) $item['position'],
                    'trim_start_ms' => null,
                    'trim_end_ms' => null,
                    'image_duration_ms' => $durationMs,
                ];
            }
        }

        if ($totalMs > (int) $config['max_duration_seconds'] * 1000) {
            throw ValidationException::withMessages(['assets' => 'Duração total estimada excede o limite.']);
        }

        $request = VideoCompositionRequest::create([
            'status' => VideoCompositionRequestStatus::Pending,
            'content_script_id' => $contentScriptId,
            'created_by' => $createdBy,
        ]);

        foreach ($validated as $row) {
            $request->inputs()->create($row + ['video_composition_request_id' => $request->id]);
        }

        return $request->fresh();
    }

    /**
     * @throws VideoCompositionException
     */
    public function process(VideoCompositionRequest $request): void
    {
        $config = config('video-composition');
        $workDir = $this->tempDir($request);
        $outputPath = $workDir.'/output.mp4';

        $request->update([
            'status' => VideoCompositionRequestStatus::Processing,
            'started_at' => now(),
        ]);

        try {
            $segments = [];

            foreach ($request->inputs()->with('mediaAsset')->get() as $input) {
                $segments[] = $this->segment($input);
            }

            if ($segments === []) {
                throw new VideoCompositionException('source_missing', 'Nenhum item válido para compor.');
            }

            $this->composer->compose(CompositionPlan::defaults($segments), $workDir, $outputPath);

            $metadata = $this->inspector->inspect($outputPath);

            if ($metadata === null
                || $metadata->mimeType !== 'video/mp4'
                || $metadata->width !== (int) $config['width']
                || $metadata->height !== (int) $config['height']
                || ($metadata->durationSeconds ?? 0) <= 0
            ) {
                throw new VideoCompositionException('invalid_output', 'A composição não gerou um vídeo válido.');
            }

            $finalPath = 'videos/compositions/'.now()->format('Y/m').'/'.Str::uuid().'.mp4';
            Storage::disk('public')->put($finalPath, file_get_contents($outputPath));

            $asset = MediaAsset::create([
                'type' => MediaAssetType::Video,
                'source' => MediaAssetSource::Composed,
                'provider' => null,
                'model' => null,
                'disk' => 'public',
                'path' => $finalPath,
                'filename' => basename($finalPath),
                'mime_type' => $metadata->mimeType,
                'width' => $metadata->width,
                'height' => $metadata->height,
                'duration_seconds' => (int) round((float) $metadata->durationSeconds),
                'size_bytes' => $metadata->sizeBytes,
                'aspect_ratio' => '9:16',
                'status' => MediaAssetStatus::Ready,
                'created_by' => $request->created_by,
            ]);

            $request->update([
                'status' => VideoCompositionRequestStatus::Success,
                'output_media_asset_id' => $asset->id,
                'completed_at' => now(),
            ]);
        } catch (VideoCompositionException $e) {
            $request->update([
                'status' => VideoCompositionRequestStatus::Failed,
                'error_code' => $e->errorCode,
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        } finally {
            $this->cleanupDir($workDir);
        }
    }

    public function tempDir(VideoCompositionRequest $request): string
    {
        return storage_path('app/tmp/video-composition/'.$request->id);
    }

    public function cleanupRequestTemp(VideoCompositionRequest $request): void
    {
        $this->cleanupDir($this->tempDir($request));
    }

    /**
     * @throws VideoCompositionException
     */
    protected function segment(VideoCompositionInput $input): CompositionSegment
    {
        $asset = $input->mediaAsset;

        if (! $asset || ! Storage::disk($asset->disk)->exists($asset->path)) {
            throw new VideoCompositionException('source_missing', 'Um dos itens não está mais disponível.');
        }

        $absolute = Storage::disk($asset->disk)->path($asset->path);

        if ($asset->type === MediaAssetType::Video) {
            return new CompositionSegment(
                sourcePath: $absolute,
                kind: 'video',
                trimStartMs: $input->trim_start_ms,
                trimEndMs: $input->trim_end_ms,
            );
        }

        $this->assertImage($asset);

        return new CompositionSegment(
            sourcePath: $absolute,
            kind: 'image',
            imageDurationMs: $input->image_duration_ms ?? (int) config('video-composition.image_default_duration_ms'),
        );
    }

    /**
     * @throws VideoCompositionException
     */
    protected function assertImage(MediaAsset $asset): void
    {
        $binary = Storage::disk($asset->disk)->get($asset->path);
        $info = is_string($binary) ? @getimagesizefromstring($binary) : false;

        if ($binary === false || $info === false
            || ! in_array($info['mime'] ?? null, ['image/jpeg', 'image/png'], true)
        ) {
            throw new VideoCompositionException('source_invalid', 'Uma das imagens não é válida.');
        }
    }

    protected function videoDurationMs(MediaAsset $asset): int
    {
        if ($asset->duration_seconds) {
            return $asset->duration_seconds * 1000;
        }

        $metadata = $this->inspector->inspect(Storage::disk($asset->disk)->path($asset->path));

        if ($metadata === null || ($metadata->durationSeconds ?? 0) <= 0) {
            throw new VideoCompositionException('source_invalid', 'Um dos vídeos não é válido.');
        }

        return (int) round((float) $metadata->durationSeconds * 1000);
    }

    protected function cleanupDir(string $workDir): void
    {
        if (! is_dir($workDir)) {
            return;
        }

        foreach (glob($workDir.'/*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        @rmdir($workDir);
    }
}
