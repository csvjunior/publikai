<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Listas controladas de idiomas e mercados (Sprint 2)
    |--------------------------------------------------------------------------
    |
    | Configuração neutra compartilhada entre módulos (produtos, contas
    | sociais e futuros). Movida de config/products.php na Sprint 2 para
    | evitar duplicação. O banco armazena os códigos.
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

];
