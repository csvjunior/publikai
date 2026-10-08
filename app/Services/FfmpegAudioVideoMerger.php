<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;

/**
 * Merger FFmpeg (Sprint 5.6.3). Estratégia: vídeo normalizado (H.264,
 * yuv420p, 30fps, 720×1280) + narração como ÚNICA faixa de áudio (AAC
 * 128k/48kHz, map explícito, áudio original do vídeo ignorado) com
 * duração do vídeo (`-t` = video master). Array de args — Windows/Linux.
 */
class FfmpegAudioVideoMerger implements AudioVideoMerger
{
    public function merge(AudioVideoMergePlan $plan, string $outputPath): void
    {
        if (! is_dir(dirname($outputPath)) && ! mkdir(dirname($outputPath), 0755, true)) {
            throw new AudioVideoMergeException('ffmpeg_failed', 'Não foi possível preparar o merge.');
        }

        $result = Process::run($this->mergeArguments($plan, $outputPath));

        if (! $result->successful() || ! is_file($outputPath)) {
            throw new AudioVideoMergeException('ffmpeg_failed', 'Falha ao combinar vídeo e narração.');
        }
    }

    /**
     * @return array<int, string> Argumentos do FFmpeg (testável, sem shell).
     */
    public function mergeArguments(AudioVideoMergePlan $plan, string $outputPath): array
    {
        return [
            (string) config('video-composition.ffmpeg_binary', 'ffmpeg'),
            '-y',
            '-i', $plan->videoPath,
            '-i', $plan->audioPath,
            '-map', '0:v:0',
            '-map', '1:a:0',
            '-vf', sprintf(
                'scale=%d:%d:force_original_aspect_ratio=decrease,pad=%d:%d:(ow-iw)/2:(oh-ih)/2,setsar=1,fps=%d',
                $plan->width,
                $plan->height,
                $plan->width,
                $plan->height,
                $plan->fps,
            ),
            '-c:v', 'libx264',
            '-pix_fmt', 'yuv420p',
            '-r', (string) $plan->fps,
            '-c:a', 'aac',
            '-b:a', '128k',
            '-ar', '48000',
            '-t', $this->seconds($plan->videoDurationSeconds),
            $outputPath,
        ];
    }

    protected function seconds(float $value): string
    {
        return number_format($value, 3, '.', '');
    }
}
