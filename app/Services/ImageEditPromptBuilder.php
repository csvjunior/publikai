<?php

namespace App\Services;

use App\Models\Avatar;
use App\Models\ContentBlueprint;
use App\Models\ContentScript;
use App\Models\MediaAsset;
use App\Models\Persona;
use App\Models\Product;

/**
 * Monta prompt de edição/variação (Sprint 5.5.4). Determinístico, sem IA
 * textual: pedido do usuário + contexto do Script/Avatar + guardrails.
 * Distinção explícita: source = composição a transformar; references =
 * apoio de consistência do personagem artificial.
 */
class ImageEditPromptBuilder
{
    public function build(
        string $change,
        ContentScript $script,
        Product $product,
        ContentBlueprint $blueprint,
        Persona $persona,
        Avatar $avatar,
        MediaAsset $source,
        int $referenceCount = 0,
    ): string {
        $sections = [
            'EDIT REQUEST' => $this->lines([$change]),
            'SOURCE IMAGE' => $this->lines([
                'Use the first provided image as the source composition to transform.',
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
            'COMMUNICATION' => $this->lines([
                $persona->tone ? 'Tone: '.$persona->tone : null,
                $persona->audience ? 'Audience: '.$persona->audience : null,
            ]),
            'REFERENCE SUPPORT' => $referenceCount > 0 ? $this->lines([
                'Use the additional reference images only to preserve the Avatar\'s visual identity and overall appearance.',
            ]) : [],
            'CONSTRAINTS' => [
                'Preserve the same main character and key identity traits unless the edit request says otherwise.',
                'Preserve the source intent except for the requested changes.',
                'Photorealistic unless the style demands otherwise, natural proportions.',
                'Avoid duplicated limbs or fingers when people appear.',
                'Do not render text unless explicitly requested.',
                'No visible platform UI, no watermarks, no logos unless explicitly supplied.',
                'No unrelated people or objects unless requested.',
                'Preserve the requested aspect ratio.',
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
