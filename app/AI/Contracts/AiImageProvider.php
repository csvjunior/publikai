<?php

namespace App\AI\Contracts;

use App\AI\AiImageReference;
use App\AI\Exceptions\AiProviderException;
use App\AI\Results\AiImageGenerationResult;

/**
 * Contrato mínimo de geração de imagem (Sprint 5.5.0; referência opcional
 * Sprint 5.5.2). Terceiro parâmetro nullable mantém compatibilidade.
 */
interface AiImageProvider
{
    /**
     * @param  array{aspect_ratio?: string, image_size?: string, mime_type?: string}  $options
     *
     * @throws AiProviderException
     */
    public function generate(string $prompt, array $options = [], ?AiImageReference $reference = null): AiImageGenerationResult;
}
