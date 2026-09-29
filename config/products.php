<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Listas controladas de Produtos (Sprint 1)
    |--------------------------------------------------------------------------
    |
    | O banco armazena os códigos. Sem tabelas de languages/markets nesta
    | Sprint: opções iniciais simples, fáceis de ampliar aqui.
    |
    */

    'languages' => [
        'en-US' => 'English (United States)',
        'pt-BR' => 'Português (Brasil)',
    ],

    'markets' => [
        'US' => 'United States',
        'BR' => 'Brasil',
    ],

    'currencies' => [
        'USD' => 'US Dollar',
        'BRL' => 'Real Brasileiro',
    ],

    'commission_types' => [
        'percent' => 'Percentual (%)',
        'fixed' => 'Valor fixo',
    ],

];
