<?php

namespace App\Jobs;

use App\Models\ContentProduction;
use App\Services\ContentProductionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Avança UMA produção coordenada (Sprint 5.6.5, orchestrator + auditoria).
 * Não executa IA nem espera Jobs filhos: decide o próximo passo
 * síncrono ou despacha o Job filho e encerra (continuação via relay).
 * tries=1, timeout curto (só decisões + dispatches). Exceção inesperada
 * nunca deixa production presa: marca failed seguro + report, sem rethrow
 * (sem retry caro). failed() cobre morte fora do handle (hard timeout).
 */
class RunContentProductionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(public int $contentProductionId) {}

    public function handle(ContentProductionService $service): void
    {
        $production = DB::transaction(function () {
            $record = ContentProduction::whereKey($this->contentProductionId)
                ->lockForUpdate()
                ->first();

            return $record && ! $record->isTerminal() ? $record : null;
        });

        if ($production === null) {
            return;
        }

        try {
            $service->advance($production->id);
        } catch (\Throwable $e) {
            $service->failUnexpected($production->id);

            report($e);
        }
    }

    /**
     * Morte fora do handle (hard timeout do worker): nunca deixa a
     * produção presa em pending/processing. Idempotente.
     */
    public function failed(?\Throwable $exception = null): void
    {
        try {
            app(ContentProductionService::class)->failUnexpected($this->contentProductionId);
        } catch (\Throwable $e) {
            report($e);
        }

        if ($exception) {
            report($exception);
        }
    }
}
