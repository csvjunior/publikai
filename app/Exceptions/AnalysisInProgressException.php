<?php

namespace App\Exceptions;

use Exception;

class AnalysisInProgressException extends Exception
{
    public function __construct()
    {
        parent::__construct('Já existe uma análise em andamento para esta referência.');
    }
}
