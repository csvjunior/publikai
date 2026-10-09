<?php

namespace App\Services;

use App\Enums\AudioVideoDurationPolicy;
use App\Enums\AudioVideoMergeRequestStatus;
use App\Enums\MediaAssetSource;
use App\Enums\MediaAssetStatus;
use App\Enums\MediaAssetType;
use App\Models\AudioVideoMergeRequest;
use App\Models\ContentScript;
use App\Models\MediaAsset;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Merge local vídeo + narração (Sprint 5.6.3, FFmpeg).
 * createRequest(): valida snapshot (mesmo Script, tipos, merged fora).
 * process(): executado pelo Job — inspeciona, monta plano video_master,
 * executa merger, valida output com áudio AAC, MediaAsset video/merged.
 * Temp sempre limpo. Sem shell cru.
 */
class AudioVideoMergeService
{
    /**
     * @var string[]
     */
    private const ALLOWED_VIDEO_SOURCES = ['ai_generated', 'composed'];

    public function __construct(
        protected AudioVideoMerger $merger,
        protected VideoInspector $videoInspector,
        protected AudioInspector $audioInspector,
    ) {}

    /**
     * @throws ValidationException
     */
    public function createRequest(int $contentScriptId, int $videoId, int $audioId, ?int $createdBy = null, ?int $productionId = null): AudioVideoMergeRequest
    {
        $script = ContentScript::findOrFail($contentScriptId);

        $video = MediaAsset::find($videoId);
        $audio = MediaAsset::find($audioId);

        abort_unless($video && $audio, 404);

        $this->assertEligible($script, $video, 'video');
        $this->assertEligible($script, $audio, 'audio');

        if (! in_array($video->source->value, self::ALLOWED_VIDEO_SOURCES, true)) {
            throw ValidationException::withMessages(['video' => 'Este vídeo não pode ser base de merge.']);
        }

        return AudioVideoMergeRequest::create([
            'status' => AudioVideoMergeRequestStatus::Pending,
            'duration_policy' => AudioVideoDurationPolicy::VideoMaster,
            'content_script_id' => $contentScriptId,
            'content_production_id' => $productionId,
            'video_media_asset_id' => $video->id,
            'audio_media_asset_id' => $audio->id,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * @throws AudioVideoMergeException
     */
    public function process(AudioVideoMergeRequest $request): void
    {
        $workDir = storage_path('app/tmp/audio-video-merge/'.$request->id);
        $outputPath = $workDir.'/output.mp4';

        $request->update([
            'status' => AudioVideoMergeRequestStatus::Processing,
            'started_at' => now(),
        ]);

        try {
            $video = MediaAsset::find($request->video_media_asset_id);
            $audio = MediaAsset::find($request->audio_media_asset_id);

            if (! $video || ! Storage::disk($video->disk)->exists($video->path)) {
                throw new AudioVideoMergeException('video_missing', 'O vídeo base não está mais disponível.');
            }

            if (! $audio || ! Storage::disk($audio->disk)->exists($audio->path)) {
                throw new AudioVideoMergeException('audio_missing', 'A narração não está mais disponível.');
            }

            $videoMeta = $this->videoInspector->inspect(Storage::disk($video->disk)->path($video->path));
            $audioMeta = $this->audioInspector->inspect(Storage::disk($audio->disk)->path($audio->path));

            if ($videoMeta === null || ($videoMeta->durationSeconds ?? 0) <= 0) {
                throw new AudioVideoMergeException('video_invalid', 'O vídeo base não é mais válido.');
            }

            if ($audioMeta === null || ($audioMeta->durationSeconds ?? 0) <= 0) {
                throw new AudioVideoMergeException('audio_invalid', 'A narração não é mais válida.');
            }

            $config = config('video-composition');

            $this->merger->merge(new AudioVideoMergePlan(
                videoPath: Storage::disk($video->disk)->path($video->path),
                audioPath: Storage::disk($audio->disk)->path($audio->path),
                videoDurationSeconds: (float) $videoMeta->durationSeconds,
                width: (int) $config['width'],
                height: (int) $config['height'],
                fps: (int) $config['fps'],
            ), $outputPath);

            $outMeta = $this->videoInspector->inspect($outputPath);

            if ($outMeta === null
                || $outMeta->mimeType !== 'video/mp4'
                || ! $outMeta->hasAudio
                || $outMeta->audioCodec !== 'aac'
                || ($outMeta->durationSeconds ?? 0) <= 0
            ) {
                throw new AudioVideoMergeException('invalid_output', 'O merge não gerou um vídeo válido com narração.');
            }

            $finalPath = 'videos/merged/'.now()->format('Y/m').'/'.Str::uuid().'.mp4';
            Storage::disk('public')->put($finalPath, file_get_contents($outputPath));

            $asset = MediaAsset::create([
                'type' => MediaAssetType::Video,
                'source' => MediaAssetSource::Merged,
                'provider' => null,
                'model' => null,
                'disk' => 'public',
                'path' => $finalPath,
                'filename' => basename($finalPath),
                'mime_type' => $outMeta->mimeType,
                'width' => $outMeta->width,
                'height' => $outMeta->height,
                'duration_seconds' => (int) round((float) $outMeta->durationSeconds),
                'size_bytes' => $outMeta->sizeBytes,
                'aspect_ratio' => '9:16',
                'status' => MediaAssetStatus::Ready,
                'created_by' => $request->created_by,
            ]);

            $request->update([
                'status' => AudioVideoMergeRequestStatus::Success,
                'output_media_asset_id' => $asset->id,
                'completed_at' => now(),
            ]);
        } catch (AudioVideoMergeException $e) {
            $request->update([
                'status' => AudioVideoMergeRequestStatus::Failed,
                'error_code' => $e->errorCode,
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        } finally {
            $this->cleanupDir($workDir);
        }
    }

    public function tempDir(AudioVideoMergeRequest $request): string
    {
        return storage_path('app/tmp/audio-video-merge/'.$request->id);
    }

    public function cleanupRequestTemp(AudioVideoMergeRequest $request): void
    {
        $this->cleanupDir($this->tempDir($request));
    }

    protected function assertEligible(ContentScript $script, MediaAsset $asset, string $kind): void
    {
        $inScript = $this->assetInScript($script, $asset);

        abort_unless($inScript, 404);

        $ok = $kind === 'video'
            ? $asset->type === MediaAssetType::Video
            : $asset->type === MediaAssetType::Audio;

        abort_unless($ok && $asset->status === MediaAssetStatus::Ready, 422, 'Item inválido para merge.');
    }

    protected function assetInScript(ContentScript $script, MediaAsset $asset): bool
    {
        if ($script->mediaAssets()->whereKey($asset->id)->exists()) {
            return true;
        }

        if ($asset->type === MediaAssetType::Video) {
            return $script->videoRequests()->where('media_asset_id', $asset->id)->exists()
                || $script->compositions()->where('output_media_asset_id', $asset->id)->exists()
                || $script->merges()->where('output_media_asset_id', $asset->id)->exists();
        }

        return $script->audioRequests()->where('media_asset_id', $asset->id)->exists();
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
