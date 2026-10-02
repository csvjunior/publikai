<?php

namespace App\Jobs;

use App\Enums\ImageGenerationRequestStatus;
use App\Models\ImageGenerationRequest;
use App\Services\ImageGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Executa UMA geração de imagem (Sprint 5.5.0 async).
 * tries=1 (custo; sem retry automático), timeout 90s. Idempotente:
 * ignora requests já terminais. Falhas tratáveis viram failed no domínio;
 * inesperadas também (internal_error) + report, sem rethrow.
 */
class GenerateImageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 90;

    public function __construct(public int $imageGenerationRequestId) {}

    public function handle(ImageGenerationService $service): void
    {
        $request = DB::transaction(function () {
            $record = ImageGenerationRequest::whereKey($this->imageGenerationRequestId)
                ->lockForUpdate()
                ->first();

            if (! $record || $record->isTerminal()) {
                return null;
            }

            $record->update([
                'status' => ImageGenerationRequestStatus::Processing,
                'started_at' => now(),
            ]);

            return $record;
        });

        if ($request === null) {
            return;
        }

        try {
            $service->process($request);
        } catch (\Throwable $e) {
            $request->update([
                'status' => ImageGenerationStatus::Failed,
                'error_code' => 'internal_error',
                'error_message' => 'Falha inesperada na geração da imagem.',
                'completed_at' => now(),
            ]);

            report($e);
        }
    }

    /**
     * Invocado pelo worker quando o Job falha fora do handle (ex.: hard
     * timeout após 90s): garante que o request nunca fique preso em
     * processing. Sem rethrow (tries=1, sem retry).
     */
    public function failed(?\Throwable $exception = null): void
    {
        $request = ImageGenerationRequest::find($this->imageGenerationRequestId);

        if ($request && ! $request->isTerminal()) {
            $request->update([
                'status' => ImageGenerationRequestStatus::Failed,
                'error_code' => 'timeout',
                'error_message' => 'A geração excedeu o tempo esperado.',
                'completed_at' => now(),
            ]);
        }
    }
}
