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
        // Orçamento síncrono: 10s/tentativa + connect 5s, 1 retry só p/ 429/5xx
        // → pior caso ~20s, confortavelmente abaixo do max_execution_time (30s).
        // Operações longas deverão migrar para Job/fila.
        'timeout' => (int) env('GOOGLE_AI_TIMEOUT', 10),
        'connect_timeout' => (int) env('GOOGLE_AI_CONNECT_TIMEOUT', 5),

        // Imagem (Sprint 5.5.0): mesma auth key (mesma API/família de endpoint).
        // Sem retry: 1 tentativa. Orçamento do Job (fora do request web).
        'image' => [
            'enabled' => (bool) env('GOOGLE_AI_IMAGE_ENABLED', false),
            'model' => env('GOOGLE_AI_IMAGE_MODEL', 'gemini-3.1-flash-image'),
            'timeout' => (int) env('GOOGLE_AI_IMAGE_TIMEOUT', 60),
            'connect_timeout' => (int) env('GOOGLE_AI_IMAGE_CONNECT_TIMEOUT', 5),
            'default_mime_type' => env('GOOGLE_AI_IMAGE_MIME_TYPE', 'image/jpeg'),
            'default_aspect_ratio' => env('GOOGLE_AI_IMAGE_ASPECT_RATIO', '9:16'),
            'default_size' => env('GOOGLE_AI_IMAGE_SIZE', '1K'),
        ],
    ],

];
