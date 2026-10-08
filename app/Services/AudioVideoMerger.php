<?php

namespace App\Services;

/**
 * Merge de narração em vídeo (Sprint 5.6.3).
 * Produção usa FFmpeg; testes injetam fake. Sem shell cru: array de args.
 */
interface AudioVideoMerger
{
    /**
     * @throws AudioVideoMergeException
     */
    public function merge(AudioVideoMergePlan $plan, string $outputPath): void;
}
