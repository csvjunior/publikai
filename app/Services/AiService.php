<?php

namespace App\Services;

use App\AI\Contracts\AiTextProvider;
use App\AI\Exceptions\AiProviderException;
use App\AI\Results\AiGenerationResult;
use App\Enums\AiGenerationStatus;
use App\Models\AiGeneration;

/**
 * Orquestra chamadas de IA com logging sanitizado (Sprint 5.0).
 * O provider permanece puro (sem side effects); este service registra
 * sucesso/falha em ai_generations — sem credenciais, sem prompts.
 */
class AiService
{
    public function __construct(protected AiTextProvider $provider) {}

    public function testConnection(): AiGenerationResult
    {
        $log = AiGeneration::create([
            'provider' => config('ai.provider', 'google'),
            'model' => (string) config('ai.google.model'),
            'operation' => 'connection_test',
            'status' => AiGenerationStatus::Pending,
        ]);

        $started = microtime(true);

        try {
            $result = $this->provider->generateStructured(
                operation: 'connection_test',
                instructions: 'Return a connectivity test result.',
                input: 'Connectivity test from Publikai.',
                schema: [
                    'type' => 'object',
                    'properties' => [
                        'status' => ['type' => 'string'],
                        'message' => ['type' => 'string'],
                    ],
                    'required' => ['status', 'message'],
                ],
            );
        } catch (AiProviderException $e) {
            $log->update([
                'status' => AiGenerationStatus::Failed,
                'error_code' => $e->errorCode,
                'metadata' => $e->details,
                'duration_ms' => (int) ((microtime(true) - $started) * 1000),
            ]);

            throw $e;
        }

        $log->update([
            'status' => AiGenerationStatus::Success,
            'input_tokens' => $result->inputTokens,
            'output_tokens' => $result->outputTokens,
            'duration_ms' => $result->durationMs,
            'external_request_id' => $result->externalRequestId,
        ]);

        return $result;
    }
}
