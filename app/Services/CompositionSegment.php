<?php

namespace App\Services;

/**
 * Segmento normalizado de composição (Sprint 5.6.1).
 * Vídeo: trim em ms (null = inteiro). Imagem: duração do segmento em ms.
 */
class CompositionSegment
{
    public function __construct(
        public readonly string $sourcePath,
        public readonly string $kind,
        public readonly ?int $trimStartMs = null,
        public readonly ?int $trimEndMs = null,
        public readonly ?int $imageDurationMs = null,
    ) {}
}
