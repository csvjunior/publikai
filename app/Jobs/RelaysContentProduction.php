<?php

namespace App\Jobs;

use App\Models\AudioGenerationRequest;
use App\Models\AudioVideoMergeRequest;
use App\Models\ImageGenerationRequest;
use App\Models\VideoGenerationRequest;
use App\Services\ContentProductionService;

/**
 * Integração opcional filho → orchestrator (Sprint 5.6.5).
 * Só age quando o request carrega content_production_id; fluxos manuais
 * seguem funcionando sozinhos. Chamado no fim do handle e no failed().
 */
trait RelaysContentProduction
{
    protected function relayToProduction(
        ImageGenerationRequest|VideoGenerationRequest|AudioGenerationRequest|AudioVideoMergeRequest $request,
    ): void {
        $productionId = $request->content_production_id;

        if (! $productionId) {
            return;
        }

        try {
            app(ContentProductionService::class)->childFinished((int) $productionId);
        } catch (\Throwable $e) {
            // Continuação inesperada nunca deixa a produção presa:
            // marca failed seguro (best-effort) + report.
            try {
                app(ContentProductionService::class)->failUnexpected((int) $productionId);
            } catch (\Throwable $inner) {
                report($inner);
            }

            report($e);
        }
    }
}
