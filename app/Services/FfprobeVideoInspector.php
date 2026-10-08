<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;

/**
 * Inspetor via FFprobe (Sprint 5.6.0). Binário via env `FFPROBE_BINARY`
 * (sem path hardcoded); sem interpolação de input em shell (Process com
 * array). Só leitura de metadata.
 */
class FfprobeVideoInspector implements VideoInspector
{
    /**
     * @var string[]
     */
    private const ALLOWED_MIMES = ['video/mp4', 'video/webm', 'video/quicktime'];

    public function inspect(string $path): ?VideoMetadata
    {
        if (! is_file($path)) {
            return null;
        }

        $result = Process::run([
            (string) config('video-inspector.binary', 'ffprobe'),
            '-v', 'error',
            '-show_entries', 'format=duration,size',
            '-show_entries', 'stream=index,codec_type,codec_name,width,height',
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

        $videoStream = null;
        $audioStream = null;

        foreach ((array) ($json['streams'] ?? []) as $stream) {
            if (! is_array($stream)) {
                continue;
            }

            if (($stream['codec_type'] ?? null) === 'video' && $videoStream === null) {
                $videoStream = $stream;
            }

            if (($stream['codec_type'] ?? null) === 'audio' && $audioStream === null) {
                $audioStream = $stream;
            }
        }

        $format = $json['format'] ?? [];

        if ($videoStream === null || ($videoStream['width'] ?? 0) <= 0) {
            return null;
        }

        $mime = $this->mimeFor($path);

        if ($mime === null) {
            return null;
        }

        return new VideoMetadata(
            mimeType: $mime,
            width: (int) $videoStream['width'],
            height: (int) ($videoStream['height'] ?? 0) ?: null,
            durationSeconds: is_numeric($format['duration'] ?? null) ? (float) $format['duration'] : null,
            sizeBytes: is_numeric($format['size'] ?? null) ? (int) $format['size'] : null,
            hasAudio: $audioStream !== null,
            videoCodec: is_string($videoStream['codec_name'] ?? null) ? $videoStream['codec_name'] : null,
            audioCodec: is_string($audioStream['codec_name'] ?? null) ? $audioStream['codec_name'] : null,
        );
    }

    protected function mimeFor(string $path): ?string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $mime = match ($ext) {
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'mov' => 'video/quicktime',
            default => null,
        };

        return in_array($mime, self::ALLOWED_MIMES, true) ? $mime : null;
    }
}
