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
            // Referências de personagem (Sprint 5.5.3): gemini-3.1-flash-image
            // aceita até 4 imagens de personagem (docs oficiais); sem segredo.
            'max_references' => (int) env('GOOGLE_AI_IMAGE_MAX_REFERENCES', 4),
        ],

        // Vídeo (Sprint 5.6.0, Omni Flash via Interactions REST).
        // Chamada síncrona (sem operação/polling); async via Job/queue.
        // HTTP longo: resposta carrega o vídeo em base64.
        'video' => [
            'enabled' => (bool) env('GOOGLE_AI_VIDEO_ENABLED', false),
            'model' => env('GOOGLE_AI_VIDEO_MODEL', 'gemini-omni-1.1-flash'),
            'timeout' => (int) env('GOOGLE_AI_VIDEO_TIMEOUT', 300),
            'connect_timeout' => (int) env('GOOGLE_AI_VIDEO_CONNECT_TIMEOUT', 10),
            'default_aspect_ratio' => env('GOOGLE_AI_VIDEO_DEFAULT_ASPECT_RATIO', '9:16'),
            'default_duration' => (int) env('GOOGLE_AI_VIDEO_DEFAULT_DURATION', 8),
            'max_download_bytes' => (int) env('GOOGLE_AI_VIDEO_MAX_DOWNLOAD_BYTES', 104857600),
        ],

        // Áudio/TTS (Sprint 5.6.2, Gemini 3.8 Flash TTS via Interactions REST).
        // Chamada síncrona (sem operação/polling); async via Job/queue.
        // Vozes: somente prebuilt oficiais (sem design/replication/cloning).
        'audio' => [
            'enabled' => (bool) env('GOOGLE_AI_AUDIO_ENABLED', false),
            'model' => env('GOOGLE_AI_AUDIO_MODEL', 'gemini-3.8-flash-tts'),
            'timeout' => (int) env('GOOGLE_AI_AUDIO_TIMEOUT', 120),
            'connect_timeout' => (int) env('GOOGLE_AI_AUDIO_CONNECT_TIMEOUT', 10),
            'default_voice' => env('GOOGLE_AI_AUDIO_DEFAULT_VOICE', 'Kore'),
            'max_text_length' => (int) env('GOOGLE_AI_AUDIO_MAX_TEXT_LENGTH', 2000),
            'voices' => [
                'Kore' => 'Kore · Firme',
                'Puck' => 'Puck · Animada',
                'Charon' => 'Charon · Informativa',
                'Fenrir' => 'Fenrir · Empolgada',
                'Leda' => 'Leda · Jovem',
                'Aoede' => 'Aoede · Leve',
                'Callirrhoe' => 'Callirrhoe · Tranquila',
                'Autonoe' => 'Autonoe · Clara',
            ],
        ],
    ],

];
