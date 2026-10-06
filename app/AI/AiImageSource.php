<?php

namespace App\AI;

/**
 * Imagem base (source) de uma edição/variação (Sprint 5.5.4).
 * Distinta de AiImageReference: a source é a composição a transformar;
 * references são apoio de consistência do Avatar. Binário só em memória.
 */
class AiImageSource
{
    public function __construct(
        public readonly string $binary,
        public readonly string $mimeType,
        public readonly ?int $width = null,
        public readonly ?int $height = null,
    ) {}
}
