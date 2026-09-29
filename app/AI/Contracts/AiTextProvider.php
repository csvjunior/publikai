<?php

namespace App\AI\Contracts;

use App\AI\Exceptions\AiProviderException;
use App\AI\Results\AiGenerationResult;

/**
 * Contrato mínimo de geração estruturada de texto (Sprint 5.0).
 * Permite trocar o provider sem tocar o restante da aplicação.
 */
interface AiTextProvider
{
    /**
     * Gera conteúdo estruturado segundo o schema JSON informado.
     *
     * @param  array<string, mixed>  $schema  JSON Schema do resultado.
     *
     * @throws AiProviderException
     */
    public function generateStructured(string $operation, string $instructions, string $input, array $schema): AiGenerationResult;
}
