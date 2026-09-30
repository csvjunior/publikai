<?php

namespace App\Exceptions;

use Exception;

class AnalysisInProgressException extends Exception
{
    public function __construct(string $message = 'Já existe uma análise em andamento para esta referência.')
    {
        parent::__construct($message);
    }
}
