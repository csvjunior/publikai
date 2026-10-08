<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Binário do FFprobe (Sprint 5.6.0)
    |--------------------------------------------------------------------------
    |
    | Usado SOMENTE para inspeção/metadata de vídeos (nunca edição).
    | Sem path hardcoded de SO: defina FFPROBE_BINARY no .env.
    |
    */

    'binary' => env('FFPROBE_BINARY', 'ffprobe'),
];
