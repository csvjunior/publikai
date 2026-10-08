<?php

namespace App\AI\Contracts;

use App\AI\AiAudioGenerationInput;
use App\AI\Exceptions\AiProviderException;
use App\AI\Results\AiAudioGenerationResult;

/**
 * Contrato mínimo de narração TTS (Sprint 5.6.2).
 * DTO normalizado, nunca Models. Sem SDK.
 */
interface AiAudioProvider
{
    /**
     * @throws AiProviderException
     */
    public function generate(AiAudioGenerationInput $input): AiAudioGenerationResult;
}
