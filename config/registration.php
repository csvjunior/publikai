<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cadastro interno
    |--------------------------------------------------------------------------
    |
    | O Publikai é uma ferramenta interna da Jaguartec. O cadastro público
    | não existe: ele pode ser totalmente desligado (REGISTRATION_ENABLED)
    | ou protegido por um código interno (REGISTRATION_CODE).
    |
    | O código é comparado somente no servidor, via hash_equals, e nunca
    | é exposto ao frontend. Veja App\Services\RegistrationService.
    |
    */

    'enabled' => (bool) env('REGISTRATION_ENABLED', true),

    'code' => env('REGISTRATION_CODE', ''),

];
