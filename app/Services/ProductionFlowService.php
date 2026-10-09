<?php

namespace App\Services;

use App\Enums\MediaAssetSource;
use App\Enums\MediaAssetType;
use App\Models\ContentScript;
use App\Models\MediaAsset;
use Illuminate\Support\Collection;

/**
 * Jornada de produção do Script (Sprint 5.6.4).
 * View-model: analisa o estado atual e retorna etapas, status, resumos,
 * ação recomendada (UMA) e assets recomendados. Sem lógica no Blade.
 * Montagem nunca é recomendada automaticamente (sempre opcional).
 */
class ProductionFlowService
{
    /**
     * @return array{
     *     visual: array{status: string, asset: ?MediaAsset, summary: ?string, retry_url: ?string},
     *     video: array{status: string, asset: ?MediaAsset, summary: ?string, retry_url: ?string},
     *     narration: array{status: string, asset: ?MediaAsset, summary: ?string, retry_url: ?string},
     *     final: array{status: string, asset: ?MediaAsset, summary: ?string},
     *     recommended_action: string,
     *     recommended_image_id: ?int,
     *     recommended_video_id: ?int,
     *     recommended_audio_id: ?int,
     *     blocked_by_processing: bool,
     * }
     */
    public function for(ContentScript $script): array
    {
        $images = $script->mediaAssets
            ->filter(fn ($a) => $a->type === MediaAssetType::Image)
            ->values();

        $primaryImage = $images->first(fn ($a) => (bool) $a->pivot->is_primary) ?? $images->last();

        $videos = $this->successOutputs($script, 'videoRequests', 'mediaAsset')
            ->merge($this->successOutputs($script, 'compositions', 'output'))
            ->unique('id')->sortBy('id')->values();

        $video = $videos->first(fn ($a) => $a->source === MediaAssetSource::Composed) ?? $videos->last();

        $narrations = $this->successOutputs($script, 'audioRequests', 'mediaAsset');
        $narration = $narrations->last();

        $finals = $this->successOutputs($script, 'merges', 'output');
        $final = $finals->last();

        $visual = $this->step($images->isNotEmpty(), $this->isActive($script, 'imageRequests'), $this->latestFailed($script, 'imageRequests'));
        $videoStep = $this->step($videos->isNotEmpty(), $this->isActive($script, 'videoRequests'), $this->latestFailed($script, 'videoRequests'));
        $narrationStep = $this->step($narrations->isNotEmpty(), $this->isActive($script, 'audioRequests'), $this->latestFailed($script, 'audioRequests'));
        $finalStep = $this->step($final !== null, $this->isActive($script, 'merges'), $this->latestFailed($script, 'merges'));

        $recommended = match (true) {
            $final !== null => 'final_ready',
            $video !== null && $narration !== null => 'finalize_video',
            $video !== null => 'generate_audio',
            $primaryImage !== null => 'create_video',
            default => 'generate_image',
        };

        $blockedByProcessing = match ($recommended) {
            'generate_image' => $visual['status'] === 'processing',
            'create_video' => $videoStep['status'] === 'processing',
            'generate_audio' => $narrationStep['status'] === 'processing',
            'finalize_video' => $finalStep['status'] === 'processing',
            default => false,
        };

        return [
            'visual' => [
                'status' => $visual['status'],
                'asset' => $primaryImage,
                'summary' => $primaryImage ? 'Imagem pronta' : null,
                'retry_url' => $visual['failed'] ? route('scripts.images.create', $script) : null,
            ],
            'video' => [
                'status' => $videoStep['status'],
                'asset' => $video,
                'summary' => $video ? 'Vídeo pronto' : null,
                'retry_url' => $videoStep['failed'] ? route('scripts.videos.create', $script) : null,
            ],
            'narration' => [
                'status' => $narrationStep['status'],
                'asset' => $narration,
                'summary' => $narration ? 'Narração pronta' : null,
                'retry_url' => $narrationStep['failed'] ? route('scripts.audio.create', $script) : null,
            ],
            'final' => [
                'status' => $finalStep['status'],
                'asset' => $final,
                'summary' => $final ? 'Vídeo final pronto' : null,
            ],
            'recommended_action' => $recommended,
            'recommended_image_id' => $primaryImage?->id,
            'recommended_video_id' => $video?->id,
            'recommended_audio_id' => $narration?->id,
            'blocked_by_processing' => $blockedByProcessing,
        ];
    }

    /**
     * @return array{status: string, failed: bool}
     */
    protected function step(bool $hasAsset, bool $active, bool $failed): array
    {
        if ($hasAsset) {
            return ['status' => 'ready', 'failed' => false];
        }

        if ($active) {
            return ['status' => 'processing', 'failed' => false];
        }

        if ($failed) {
            return ['status' => 'failed', 'failed' => true];
        }

        return ['status' => 'empty', 'failed' => false];
    }

    /**
     * @return Collection<int, MediaAsset>
     */
    protected function successOutputs(ContentScript $script, string $relation, string $output): Collection
    {
        $requests = $script->relationLoaded($relation)
            ? $script->getRelation($relation)
            : $script->{$relation}()->limit(10)->get();

        return $requests
            ->filter(fn ($r) => $r->status->value === 'success' && $r->{$output} !== null)
            ->map(fn ($r) => $r->{$output})
            ->filter()
            ->values();
    }

    protected function isActive(ContentScript $script, string $relation): bool
    {
        $requests = $script->relationLoaded($relation)
            ? $script->getRelation($relation)
            : $script->{$relation}()->limit(10)->get();

        return $requests->contains(fn ($r) => in_array($r->status->value, ['pending', 'processing', 'starting'], true));
    }

    protected function latestFailed(ContentScript $script, string $relation): bool
    {
        $requests = $script->relationLoaded($relation)
            ? $script->getRelation($relation)
            : $script->{$relation}()->limit(10)->get();

        return $requests->sortByDesc('id')->first()?->status->value === 'failed';
    }
}
