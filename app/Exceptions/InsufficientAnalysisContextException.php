<?php

namespace App\Exceptions;

use Exception;

class InsufficientAnalysisContextException extends Exception
{
    public function __construct(string $message = 'Cadastre ao menos um conteúdo ativo nesta referência antes de analisar.')
    {
        parent::__construct($message);
    }
}
