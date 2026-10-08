<?php

namespace App\AI;

/**
 * Entrada de narração TTS (Sprint 5.6.2).
 * Somente parâmetros oficialmente suportados: texto, voz prebuilt e
 * estilo de entrega (Persona tone). Sem voz física/inferências.
 */
class AiAudioGenerationInput
{
    public function __construct(
        public readonly string $text,
        public readonly string $voice,
        public readonly ?string $style = null,
    ) {}
}
