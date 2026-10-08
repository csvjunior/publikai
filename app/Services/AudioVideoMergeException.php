<?php

namespace App\Services;

/**
 * Erro de merge com código apresentável (Sprint 5.6.3).
 * Mensagens nunca contêm stderr técnico completo ou paths sensíveis.
 */
class AudioVideoMergeException extends \Exception
{
    public function __construct(
        public readonly string $errorCode,
        string $message = 'Falha ao combinar vídeo e narração.',
    ) {
        parent::__construct($message);
    }
}
