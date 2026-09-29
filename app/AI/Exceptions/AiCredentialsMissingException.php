<?php

namespace App\AI\Exceptions;

class AiCredentialsMissingException extends AiProviderException
{
    public function __construct()
    {
        parent::__construct('credentials_missing', 'Credencial do provider de IA não configurada.');
    }
}
