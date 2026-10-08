<?php

namespace App\Jobs;

use App\Enums\VideoGenerationRequestStatus;
use App\Models\VideoGenerationRequest;
use App\Services\VideoGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Executa UMA geração de vídeo (Sprint 5.6.0, Omni Flash síncrono).
 * tries=1 (custo; sem retry), timeout 600s (operação longa + polling).
 * Idempotente: ignora requests terminais. Falhas tratáveis viram failed;
 * inesperadas também (internal_error) + report, sem rethrow.
 */
class GenerateVideoJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public int $videoGenerationRequestId) {}

    public function handle(VideoGenerationService $service): void
    {
        $request = DB::transaction(function () {
            $record = VideoGenerationRequest::whereKey($this->videoGenerationRequestId)
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
                'status' => VideoGenerationRequestStatus::Failed,
                'error_code' => 'internal_error',
                'error_message' => 'Falha inesperada na geração do vídeo.',
                'completed_at' => now(),
            ]);

            report($e);
        }
    }

    /**
     * Garante que o request nunca fique preso em processing/starting se o
     * Job morrer fora do handle (hard timeout). Sem rethrow (tries=1).
     */
    public function failed(?\Throwable $exception = null): void
    {
        $request = VideoGenerationRequest::find($this->videoGenerationRequestId);

        if ($request && ! $request->isTerminal()) {
            $request->update([
                'status' => VideoGenerationRequestStatus::Failed,
                'error_code' => 'timeout',
                'error_message' => 'A geração do vídeo excedeu o tempo esperado.',
                'completed_at' => now(),
            ]);
        }
    }
}
