<?php

namespace App\Services;

use App\Enums\ContentProductionStatus;
use App\Enums\ContentScriptStatus;
use App\Enums\ContentType;
use App\Models\ContentProduction;
use App\Models\ContentScript;

/**
 * Status amigável ÚNICO do conteúdo (microajuste 5.6.5).
 * Para vídeo com produção relevante, a produção tem prioridade sobre o
 * roteiro; sem produção (ou conteúdo imagem), vale o contrato atual do
 * Script. Sem lógica no Blade: a view chama apenas `for()`.
 */
class ContentStatusResolver
{
    public function for(ContentScript $script): string
    {
        $production = $this->relevantProduction($script);

        if ($production && $script->resolveContentType() === ContentType::Video) {
            return match ($production->status) {
                ContentProductionStatus::Pending,
                ContentProductionStatus::Processing => 'Processando',
                ContentProductionStatus::Success => 'Pronto',
                ContentProductionStatus::Failed => 'Falhou',
            };
        }

        $base = $script->resolveContentStatus();

        if ($base === 'Roteiro para revisar' && $script->status === ContentScriptStatus::Approved) {
            return 'Pronto para produzir';
        }

        return $base;
    }

    /**
     * Produção que define o estado atual: ativa mais recente; sem ativa,
     * a última. Usa a relação já carregada quando disponível (sem N+1).
     */
    protected function relevantProduction(ContentScript $script): ?ContentProduction
    {
        if ($script->relationLoaded('productions')) {
            $ordered = $script->getRelation('productions')->sortByDesc('id')->values();

            return $ordered->first(fn (ContentProduction $p) => $p->isActive())
                ?? $ordered->first();
        }

        return $script->productions()
            ->whereIn('status', [ContentProductionStatus::Pending, ContentProductionStatus::Processing])
            ->latest('id')
            ->first()
            ?? $script->productions()->latest('id')->first();
    }
}
