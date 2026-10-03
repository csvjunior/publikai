<?php

namespace App\AI\Contracts;

use App\AI\AiImageReference;
use App\AI\Exceptions\AiProviderException;
use App\AI\Results\AiImageGenerationResult;

/**
 * Contrato mínimo de geração de imagem (Sprint 5.5.0; referências Sprint
 * 5.5.2 singular, 5.5.3 lista). DTOs normalizados, nunca Models.
 */
interface AiImageProvider
{
    /**
     * @param  array{aspect_ratio?: string, image_size?: string, mime_type?: string}  $options
     * @param  list<AiImageReference>  $references
     *
     * @throws AiProviderException
     */
    public function generate(string $prompt, array $options = [], array $references = []): AiImageGenerationResult;
}
