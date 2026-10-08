<?php

namespace App\AI\Contracts;

use App\AI\Exceptions\AiProviderException;
use App\AI\Results\AiVideoGenerationResult;

/**
 * Contrato mínimo de geração de vídeo image-to-video (Sprint 5.6.0).
 * Chamada síncrona (modelo retorna o vídeo na resposta); Job/queue dão o
 * async. DTOs normalizados, nunca Models. Sem SDK.
 */
interface AiVideoProvider
{
    /**
     * @param  array{aspect_ratio?: string, duration_seconds?: int}  $options
     *
     * @throws AiProviderException
     */
    public function generate(string $prompt, string $imageBinary, string $imageMime, array $options = []): AiVideoGenerationResult;
}
