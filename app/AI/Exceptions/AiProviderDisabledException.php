<?php

namespace App\AI\Exceptions;

class AiProviderDisabledException extends AiProviderException
{
    public function __construct()
    {
        parent::__construct('provider_disabled', 'O provider de IA está desabilitado.');
    }
}
