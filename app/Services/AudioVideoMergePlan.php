<?php

namespace App\Services;

/**
 * Plano de merge vídeo + narração (Sprint 5.6.3).
 * Política video_master: output dura o vídeo; áudio menor deixa silêncio,
 * áudio maior é cortado. Sem Models.
 */
class AudioVideoMergePlan
{
    public function __construct(
        public readonly string $videoPath,
        public readonly string $audioPath,
        public readonly float $videoDurationSeconds,
        public readonly int $width,
        public readonly int $height,
        public readonly int $fps,
    ) {}
}
