<?php

namespace App\Services;

use App\Models\Avatar;
use App\Models\ContentBlueprint;
use App\Models\ContentScript;
use App\Models\MediaAsset;
use App\Models\Persona;
use App\Models\Product;

/**
 * Monta prompt de vídeo image-to-video (Sprint 5.6.0). Determinístico,
 * sem IA textual: movimento desejado + contexto do Script/Avatar.
 */
class VideoPromptBuilder
{
    public function build(
        string $motion,
        ContentScript $script,
        Product $product,
        ContentBlueprint $blueprint,
        Persona $persona,
        Avatar $avatar,
        MediaAsset $source,
    ): string {
        $sections = [
            'MOTION' => $this->lines([$motion]),
            'SOURCE IMAGE' => $this->lines([
                'Animate the provided starting image into a short video.',
                $source->width && $source->height
                    ? "Source is {$source->width}x{$source->height} ({$source->mime_type})."
                    : null,
            ]),
            'SUBJECT' => $this->lines([
                $product->name,
                $product->description,
                $product->category ? 'Category: '.$product->category : null,
            ]),
            'SCENE' => $this->lines([
                $script->visual_direction,
                $script->on_screen_text ? 'Mood hint (do not render as text): '.$script->on_screen_text : null,
            ]),
            'VISUAL STYLE' => $this->lines([
                $blueprint->visual_style,
                $blueprint->content_type,
                $blueprint->objective,
            ]),
            'AVATAR' => $this->lines([
                $avatar->name.' (fictional AI character, not a real person)',
                $avatar->visual_style,
                $avatar->default_clothing ? 'Wardrobe: '.$avatar->default_clothing : null,
            ]),
            'CONTENT PURPOSE' => $this->lines([
                $script->objective,
                $script->cta ? 'Intent: '.$script->cta : null,
            ]),
            'CONSTRAINTS' => [
                'Keep the same main character and appearance consistent throughout the clip.',
                'Preserve the source composition except for the requested motion.',
                'Photorealistic unless the style demands otherwise, natural motion.',
                'No visible platform UI, no watermarks, no logos unless explicitly supplied.',
                'Do not render text unless explicitly requested.',
            ],
        ];

        $out = [];

        foreach ($sections as $title => $lines) {
            $lines = array_values(array_filter($lines, fn ($l) => is_string($l) && trim($l) !== ''));

            if ($lines === []) {
                continue;
            }

            $out[] = $title;
            foreach ($lines as $line) {
                $out[] = '- '.$line;
            }
            $out[] = '';
        }

        return trim(implode("\n", $out));
    }

    /**
     * @param  array<int, string|null>  $lines
     * @return string[]
     */
    protected function lines(array $lines): array
    {
        return array_values(array_filter($lines, fn ($l) => is_string($l) && trim($l) !== ''));
    }
}
