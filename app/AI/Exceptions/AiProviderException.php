<?php

namespace App\AI\Exceptions;

use Exception;

/**
 * Erro sanitizado de provider de IA (Sprint 5.0, observabilidade ampliada no
 * diagnóstico controlado: carrega HTTP status + detalhe sanitizado do Google).
 * Mensagens nunca contêm corpo técnico completo, headers ou segredos.
 */
class AiProviderException extends Exception
{
    /**
     * @param  array<string, scalar|null>|null  $details  Só dados sanitizados
     *                                                    (http_status, google_code...).
     */
    public function __construct(
        public readonly string $errorCode,
        string $message = 'Falha na geração de conteúdo por IA.',
        public readonly ?int $httpStatus = null,
        public readonly ?array $details = null,
    ) {
        parent::__construct($message);
    }
}
