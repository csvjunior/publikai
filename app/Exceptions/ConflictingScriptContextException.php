<?php

namespace App\Exceptions;

use Exception;

class ConflictingScriptContextException extends Exception
{
    public function __construct(string $detail = 'Idioma ou mercado divergentes entre os contextos selecionados.')
    {
        parent::__construct($detail);
    }
}
