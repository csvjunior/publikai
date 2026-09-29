<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Provider de IA (Sprint 5.0)
    |--------------------------------------------------------------------------
    |
    | default = google (único provider implementado).
    | Modelo e credencial vêm de .env; nada de IA hardcoded no código.
    | A credencial é uma Authorization (auth) key criada no AI Studio
    | (todas as novas chaves já são auth keys) e trafega somente no
    | header x-goog-api-key — nunca em logs, telas ou testes.
    |
    */

    'provider' => env('AI_PROVIDER', 'google'),

    'google' => [
        'enabled' => (bool) env('GOOGLE_AI_ENABLED', false),
        'model' => env('GOOGLE_AI_MODEL', 'gemini-3.8-flash'),
        'base_url' => env('GOOGLE_AI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'auth_key' => env('GOOGLE_AI_AUTH_KEY', ''),
        'timeout' => (int) env('GOOGLE_AI_TIMEOUT', 30),
    ],

];
