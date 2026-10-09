<?php

namespace App\Jobs;

use App\Enums\AudioGenerationRequestStatus;
use App\Models\AudioGenerationRequest;
use App\Services\AudioGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Executa UMA narração TTS (Sprint 5.6.2).
 * tries=1 (custo; sem retry), timeout 180s. Idempotente: ignora requests
 * terminais. Falhas tratáveis viram failed; inesperadas também
 * (internal_error) + report, sem rethrow.
 */
class GenerateAudioJob implements ShouldQueue
{
    use Queueable;
    use RelaysContentProduction;

    public int $tries = 1;

    public int $timeout = 180;

    public function __construct(public int $audioGenerationRequestId) {}

    public function handle(AudioGenerationService $service): void
    {
        $request = DB::transaction(function () {
            $record = AudioGenerationRequest::whereKey($this->audioGenerationRequestId)
                ->lockForUpdate()
                ->first();

            if (! $record || $record->isTerminal()) {
                return null;
            }

            return $record;
        });

        if ($request === null) {
            return;
        }

        try {
            $service->process($request);
        } catch (\Throwable $e) {
            $request->update([
                'status' => AudioGenerationRequestStatus::Failed,
                'error_code' => 'internal_error',
                'error_message' => 'Falha inesperada na geração da narração.',
                'completed_at' => now(),
            ]);

            report($e);
        }

        $this->relayToProduction($request->fresh() ?? $request);
    }

    /**
     * Garante que o request nunca fique preso em processing se o Job
     * morrer fora do handle. Sem rethrow (tries=1).
     */
    public function failed(?\Throwable $exception = null): void
    {
        $request = AudioGenerationRequest::find($this->audioGenerationRequestId);

        if ($request && ! $request->isTerminal()) {
            $request->update([
                'status' => AudioGenerationRequestStatus::Failed,
                'error_code' => 'timeout',
                'error_message' => 'A geração da narração excedeu o tempo esperado.',
                'completed_at' => now(),
            ]);

            $this->relayToProduction($request->fresh() ?? $request);
        }
    }
}
