<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Composição local de vídeo (Sprint 5.6.1)
    |--------------------------------------------------------------------------
    |
    | Saída fixa: MP4/H.264/yuv420p 720×1280 9:16 30fps, sem áudio.
    | Binários via env (sem path hardcoded); sem interpolação em shell
    | (Process com array). Valores de validação centralizados aqui.
    |
    */

    'ffmpeg_binary' => env('FFMPEG_BINARY', 'ffmpeg'),
    'width' => (int) env('VIDEO_COMPOSITION_WIDTH', 720),
    'height' => (int) env('VIDEO_COMPOSITION_HEIGHT', 1280),
    'fps' => (int) env('VIDEO_COMPOSITION_FPS', 30),
    'max_inputs' => (int) env('VIDEO_COMPOSITION_MAX_INPUTS', 10),
    'max_duration_seconds' => (int) env('VIDEO_COMPOSITION_MAX_DURATION_SECONDS', 120),
    'image_default_duration_ms' => (int) env('VIDEO_COMPOSITION_IMAGE_DURATION_MS', 3000),
    'image_min_duration_ms' => 1000,
    'image_max_duration_ms' => 10000,
];
