<?php

namespace App\Services;

/**
 * Metadata de um arquivo de vídeo (Sprint 5.6.0).
 */
class VideoMetadata
{
    public function __construct(
        public readonly string $mimeType,
        public readonly ?int $width = null,
        public readonly ?int $height = null,
        public readonly ?float $durationSeconds = null,
        public readonly ?int $sizeBytes = null,
    ) {}
}
