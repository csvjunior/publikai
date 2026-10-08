<?php

namespace App\AI\Results;

/**
 * Resultado de narração TTS (Sprint 5.6.2).
 * Binário mantido em memória só até a persistência — nunca em logs/banco.
 */
class AiAudioGenerationResult
{
    public function __construct(
        public readonly string $audioData,
        public readonly string $mimeType,
        public readonly string $provider,
        public readonly string $model,
        public readonly ?float $durationSeconds = null,
        public readonly ?int $sampleRate = null,
        public readonly ?string $externalRequestId = null,
        public readonly int $durationMs = 0,
    ) {}

    public function sizeBytes(): int
    {
        return strlen($this->audioData);
    }
}
