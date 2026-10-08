<?php

namespace App\Services;

/**
 * Metadata de um arquivo de áudio (Sprint 5.6.2).
 */
class AudioMetadata
{
    public function __construct(
        public readonly string $mimeType,
        public readonly ?float $durationSeconds = null,
        public readonly ?int $sampleRate = null,
        public readonly ?int $channels = null,
        public readonly ?int $sizeBytes = null,
    ) {}
}
