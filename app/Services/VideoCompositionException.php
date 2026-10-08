<?php

namespace App\Services;

/**
 * Erro de composição com código apresentável (Sprint 5.6.1).
 * Mensagens nunca contêm stderr técnico completo ou paths sensíveis.
 */
class VideoCompositionException extends \Exception
{
    public function __construct(
        public readonly string $errorCode,
        string $message = 'Falha na composição do vídeo.',
    ) {
        parent::__construct($message);
    }
}
