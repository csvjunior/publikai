<?php

namespace App\AI\Results;

/**
 * Resultado de geração de imagem (Sprint 5.5.0).
 * Binário mantido em memória só até a persistência — nunca em logs/banco.
 */
class AiImageGenerationResult
{
    public function __construct(
        public readonly string $imageData,
        public readonly string $mimeType,
        public readonly string $provider,
        public readonly string $model,
        public readonly ?int $width = null,
        public readonly ?int $height = null,
        public readonly ?string $externalRequestId = null,
        public readonly int $durationMs = 0,
    ) {}

    public function sizeBytes(): int
    {
        return strlen($this->imageData);
    }
}
