<?php

namespace App\Jobs;

use App\Enums\VideoCompositionRequestStatus;
use App\Models\VideoCompositionRequest;
use App\Services\VideoCompositionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Executa UMA composição local de vídeo (Sprint 5.6.1, FFmpeg).
 * tries=1, timeout 300s. Idempotente: ignora terminais. Falhas
 * inesperadas viram failed + report, sem rethrow.
 */
class ComposeVideoJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(public int $videoCompositionRequestId) {}

    public function handle(VideoCompositionService $service): void
    {
        $request = DB::transaction(function () {
            $record = VideoCompositionRequest::whereKey($this->videoCompositionRequestId)
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
                'status' => VideoCompositionRequestStatus::Failed,
                'error_code' => 'timeout',
                'error_message' => 'A composição excedeu o tempo esperado.',
                'completed_at' => now(),
            ]);

            $service->cleanupRequestTemp($request);

            report($e);
        }
    }

    /**
     * Garante request nunca preso + temp limpo se o Job morrer fora do
     * handle. Sem rethrow (tries=1).
     */
    public function failed(?\Throwable $exception = null): void
    {
        $request = VideoCompositionRequest::find($this->videoCompositionRequestId);

        if ($request && ! $request->isTerminal()) {
            $request->update([
                'status' => VideoCompositionRequestStatus::Failed,
                'error_code' => 'timeout',
                'error_message' => 'A composição excedeu o tempo esperado.',
                'completed_at' => now(),
            ]);
        }
    }
}
