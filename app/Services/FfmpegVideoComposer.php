<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;

/**
 * Compositor FFmpeg (Sprint 5.6.1). Estratégia: normaliza cada segmento
 * para o formato fixo (scale+pad sem distorção, 30fps, H.264, sem áudio)
 * e concatena via demuxer (codec copy). Args em array — Windows e Linux.
 */
class FfmpegVideoComposer implements VideoComposer
{
    public function compose(CompositionPlan $plan, string $workDir, string $outputPath): void
    {
        if (! is_dir($workDir) && ! mkdir($workDir, 0755, true)) {
            throw new VideoCompositionException('ffmpeg_failed', 'Não foi possível preparar a composição.');
        }

        $segments = [];

        foreach ($plan->segments as $index => $segment) {
            $segments[] = $this->normalize($plan, $segment, $workDir, $index);
        }

        $listFile = $workDir.'/concat.txt';
        $lines = array_map(fn ($p) => "file '".$this->escapePath($p)."'", $segments);
        file_put_contents($listFile, implode("\n", $lines)."\n");

        $result = Process::run([
            (string) config('video-composition.ffmpeg_binary', 'ffmpeg'),
            '-y',
            '-f', 'concat',
            '-safe', '0',
            '-i', $listFile,
            '-c', 'copy',
            $outputPath,
        ]);

        if (! $result->successful() || ! is_file($outputPath)) {
            throw new VideoCompositionException('ffmpeg_failed', 'Falha ao compor o vídeo.');
        }
    }

    /**
     * @return array<int, string> Argumentos do FFmpeg (testável, sem shell).
     */
    public function normalizeArguments(CompositionPlan $plan, CompositionSegment $segment, string $outputPath): array
    {
        $filter = sprintf(
            'scale=%d:%d:force_original_aspect_ratio=decrease,pad=%d:%d:(ow-iw)/2:(oh-ih)/2,setsar=1,fps=%d',
            $plan->width,
            $plan->height,
            $plan->width,
            $plan->height,
            $plan->fps,
        );

        $args = [(string) config('video-composition.ffmpeg_binary', 'ffmpeg'), '-y'];

        if ($segment->kind === 'image') {
            $args = array_merge($args, [
                '-loop', '1',
                '-t', $this->msToSeconds($segment->imageDurationMs ?? 3000),
                '-i', $segment->sourcePath,
            ]);
        } else {
            if ($segment->trimStartMs !== null && $segment->trimStartMs > 0) {
                $args = array_merge($args, ['-ss', $this->msToSeconds($segment->trimStartMs)]);
            }

            $args = array_merge($args, ['-i', $segment->sourcePath]);

            if ($segment->trimStartMs !== null && $segment->trimEndMs !== null) {
                $args = array_merge($args, ['-t', $this->msToSeconds($segment->trimEndMs - $segment->trimStartMs)]);
            }
        }

        return array_merge($args, [
            '-vf', $filter,
            '-c:v', 'libx264',
            '-pix_fmt', 'yuv420p',
            '-r', (string) $plan->fps,
            '-an',
            $outputPath,
        ]);
    }

    protected function normalize(CompositionPlan $plan, CompositionSegment $segment, string $workDir, int $index): string
    {
        $output = $workDir.'/seg'.$index.'.mp4';

        $result = Process::run($this->normalizeArguments($plan, $segment, $output));

        if (! $result->successful() || ! is_file($output)) {
            throw new VideoCompositionException('ffmpeg_failed', 'Falha ao compor o vídeo.');
        }

        return $output;
    }

    protected function msToSeconds(int $ms): string
    {
        return number_format($ms / 1000, 3, '.', '');
    }

    protected function escapePath(string $path): string
    {
        return str_replace("'", "'\\''", str_replace('\\', '/', $path));
    }
}
