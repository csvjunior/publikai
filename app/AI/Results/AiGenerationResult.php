<?php

namespace App\AI\Results;

/**
 * Resultado sanitizado de uma geração (Sprint 5.0).
 * Sem resposta bruta, sem segredos — só o necessário.
 */
class AiGenerationResult
{
    /**
     * @param  array<string, mixed>  $data  Dados já validados contra o schema.
     */
    public function __construct(
        public readonly array $data,
        public readonly string $provider,
        public readonly string $model,
        public readonly ?int $inputTokens = null,
        public readonly ?int $outputTokens = null,
        public readonly ?string $externalRequestId = null,
        public readonly int $durationMs = 0,
    ) {}
}
