<?php

namespace App\AI\Contracts;

use App\AI\Exceptions\AiProviderException;
use App\AI\Results\AiImageGenerationResult;

/**
 * Contrato mínimo de geração de imagem (Sprint 5.5.0).
 * Sem imagens de referência nesta versão (só text-to-image).
 */
interface AiImageProvider
{
    /**
     * @param  array{aspect_ratio?: string, image_size?: string, mime_type?: string}  $options
     *
     * @throws AiProviderException
     */
    public function generate(string $prompt, array $options = []): AiImageGenerationResult;
}
