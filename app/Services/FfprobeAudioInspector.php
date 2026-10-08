<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;

/**
 * Inspetor via FFprobe (Sprint 5.6.2). Binário via `FFPROBE_BINARY`
 * (sem path hardcoded); sem interpolação em shell (Process com array).
 * Só leitura de metadata.
 */
class FfprobeAudioInspector implements AudioInspector
{
    public function inspect(string $path): ?AudioMetadata
    {
        if (! is_file($path)) {
            return null;
        }

        $result = Process::run([
            (string) config('video-inspector.binary', 'ffprobe'),
            '-v', 'error',
            '-show_entries', 'format=duration,size',
            '-show_entries', 'stream=sample_rate,channels,codec_name',
            '-select_streams', 'a:0',
            '-of', 'json',
            $path,
        ]);

        if (! $result->successful()) {
            return null;
        }

        $json = json_decode($result->output(), true);

        if (! is_array($json)) {
            return null;
        }

        $stream = $json['streams'][0] ?? null;
        $format = $json['format'] ?? [];

        if (! is_array($stream)) {
            return null;
        }

        $duration = is_numeric($format['duration'] ?? null) ? (float) $format['duration'] : null;

        if ($duration === null || $duration <= 0) {
            return null;
        }

        return new AudioMetadata(
            mimeType: 'audio/wav',
            durationSeconds: $duration,
            sampleRate: is_numeric($stream['sample_rate'] ?? null) ? (int) $stream['sample_rate'] : null,
            channels: is_numeric($stream['channels'] ?? null) ? (int) $stream['channels'] : null,
            sizeBytes: is_numeric($format['size'] ?? null) ? (int) $format['size'] : null,
        );
    }
}
