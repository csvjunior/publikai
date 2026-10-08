<?php

namespace App\Services;

/**
 * Plano de composição (Sprint 5.6.1). Saída fixa via config
 * (MP4/H.264/yuv420p 720×1280 9:16 30fps, sem áudio).
 */
class CompositionPlan
{
    /**
     * @param  list<CompositionSegment>  $segments
     */
    public function __construct(
        public readonly array $segments,
        public readonly int $width,
        public readonly int $height,
        public readonly int $fps,
    ) {}

    public static function defaults(array $segments): self
    {
        $config = config('video-composition');

        return new self(
            $segments,
            (int) $config['width'],
            (int) $config['height'],
            (int) $config['fps'],
        );
    }
}
