<?php

namespace App\Jobs;

use App\Enums\AudioVideoMergeRequestStatus;
use App\Models\AudioVideoMergeRequest;
use App\Services\AudioVideoMergeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Executa UM merge local vídeo + narração (Sprint 5.6.3, FFmpeg).
 * tries=1, timeout 300s. Idempotente: ignora terminais. Falhas
 * inesperadas viram failed + report, sem rethrow.
 */
class MergeAudioVideoJob implements ShouldQueue
{
    use Queueable;
    use RelaysContentProduction;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(public int $audioVideoMergeRequestId) {}

    public function handle(AudioVideoMergeService $service): void
    {
        $request = DB::transaction(function () {
            $record = AudioVideoMergeRequest::whereKey($this->audioVideoMergeRequestId)
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
                'status' => AudioVideoMergeRequestStatus::Failed,
                'error_code' => 'timeout',
                'error_message' => 'O merge excedeu o tempo esperado.',
                'completed_at' => now(),
            ]);

            $service->cleanupRequestTemp($request);

            report($e);
        }

        $this->relayToProduction($request->fresh() ?? $request);
    }

    /**
     * Garante request nunca preso + temp limpo se o Job morrer fora do
     * handle. Sem rethrow (tries=1).
     */
    public function failed(?\Throwable $exception = null): void
    {
        $request = AudioVideoMergeRequest::find($this->audioVideoMergeRequestId);

        if ($request && ! $request->isTerminal()) {
            $request->update([
                'status' => AudioVideoMergeRequestStatus::Failed,
                'error_code' => 'timeout',
                'error_message' => 'O merge excedeu o tempo esperado.',
                'completed_at' => now(),
            ]);

            $this->relayToProduction($request->fresh() ?? $request);
        }
    }
}
