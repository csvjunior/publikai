<?php

namespace App\Exceptions;

use Exception;

class InsufficientAnalysisContextException extends Exception
{
    public function __construct()
    {
        parent::__construct('Cadastre ao menos um conteúdo ativo nesta referência antes de analisar.');
    }
}
