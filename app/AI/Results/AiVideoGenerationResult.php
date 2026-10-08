<?php

namespace App\AI\Results;

/**
 * Resultado de geração de vídeo (Sprint 5.6.0).
 * Binário mantido em memória só até a persistência — nunca em logs/banco.
 */
class AiVideoGenerationResult
{
    public function __construct(
        public readonly string $videoData,
        public readonly string $mimeType,
        public readonly string $provider,
        public readonly string $model,
        public readonly ?string $externalRequestId = null,
        public readonly int $durationMs = 0,
    ) {}

    public function sizeBytes(): int
    {
        return strlen($this->videoData);
    }
}
