<?php

namespace App\AI;

/**
 * Imagem de referência normalizada para o provider (Sprint 5.5.2).
 * Binário vive só em memória até o request HTTP — nunca em logs/banco.
 * O provider nunca recebe o Model, só estes dados.
 */
class AiImageReference
{
    public function __construct(
        public readonly string $binary,
        public readonly string $mimeType,
        public readonly ?int $width = null,
        public readonly ?int $height = null,
    ) {}
}
