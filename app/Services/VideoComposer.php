<?php

namespace App\Services;

/**
 * Composição de segmentos em um MP4 (Sprint 5.6.1).
 * Produção usa FFmpeg; testes injetam fake. Sem shell cru: array de args.
 */
interface VideoComposer
{
    /**
     * @throws VideoCompositionException
     */
    public function compose(CompositionPlan $plan, string $workDir, string $outputPath): void;
}
