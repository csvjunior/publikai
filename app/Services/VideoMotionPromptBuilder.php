<?php

namespace App\Services;

use App\Models\Avatar;
use App\Models\ContentBlueprint;
use App\Models\ContentScript;
use App\Models\Persona;
use App\Models\Product;

/**
 * Movimento padrão para o pipeline (Sprint 5.6.5).
 * Determinístico, sem IA extra: visual_direction, objetivo e contexto.
 */
class VideoMotionPromptBuilder
{
    public function build(
        ContentScript $script,
        Product $product,
        ContentBlueprint $blueprint,
        Persona $persona,
        Avatar $avatar,
    ): string {
        $parts = array_values(array_filter([
            $script->visual_direction ? 'Scene: '.$script->visual_direction : null,
            $script->objective ? 'Intent: '.$script->objective : null,
            $blueprint->visual_style ? 'Style: '.$blueprint->visual_style : null,
            $avatar->name.' moves naturally in frame',
            'Keep the character and product consistent with the source image',
            'Soft natural daylight, subtle camera movement',
        ], fn ($p) => is_string($p) && trim($p) !== ''));

        return 'Animate the starting image into a short video. '.implode('. ', $parts).'. Do not render text unless explicitly requested.';
    }
}
