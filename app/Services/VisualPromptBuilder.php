<?php

namespace App\Services;

use App\Models\Avatar;
use App\Models\ContentBlueprint;
use App\Models\ContentScript;
use App\Models\Persona;
use App\Models\Product;

/**
 * Monta prompt visual deterministicamente a partir do contexto persistido
 * (Sprint 5.5.1). Sem IA textual, sem URLs, sem ids/slugs, sem dados técnicos.
 * Persona influencia comunicação (nunca aparência); Avatar descreve personagem
 * artificial (ethnicity editorial já aprovada, sem inferência de pessoa real).
 */
class VisualPromptBuilder
{
    public function build(
        ContentScript $script,
        Product $product,
        ContentBlueprint $blueprint,
        Persona $persona,
        Avatar $avatar,
    ): string {
        $sections = [
            'SUBJECT' => $this->lines([
                $product->name,
                $product->description,
                $product->category ? 'Category: '.$product->category : null,
            ]),
            'SCENE' => $this->lines([
                $script->visual_direction,
                $avatar->preferred_scenarios,
                $script->on_screen_text ? 'Mood hint (do not render as text): '.$script->on_screen_text : null,
            ]),
            'PRODUCT CONTEXT' => $this->lines([
                $product->market ? 'Market: '.$product->market : null,
                $product->language ? 'Language context: '.$product->language : null,
                $blueprint->niche ? 'Niche: '.$blueprint->niche : null,
            ]),
            'VISUAL STYLE' => $this->lines([
                $blueprint->visual_style,
                $blueprint->content_type,
                $blueprint->objective,
            ]),
            'COMPOSITION' => $this->lines([
                $blueprint->hook_pattern,
                $blueprint->structure_pattern,
            ]),
            'LIGHTING' => ['Natural, soft daylight unless the scene demands otherwise.'],
            'AVATAR' => $this->lines([
                $avatar->name.' (fictional AI character, not a real person)',
                $avatar->apparent_age ? 'Apparent age: '.$avatar->apparent_age : null,
                $avatar->gender_presentation,
                $avatar->hair ? 'Hair: '.$avatar->hair : null,
                $avatar->eyes ? 'Eyes: '.$avatar->eyes : null,
                $avatar->skin ? 'Skin: '.$avatar->skin : null,
                $avatar->body_description,
                $avatar->default_clothing ? 'Wardrobe: '.$avatar->default_clothing : null,
                $avatar->ethnicity_description ? 'Editorial look: '.$avatar->ethnicity_description : null,
                $avatar->voice_description ? 'Presence (no audio in image): '.$avatar->voice_description : null,
            ]),
            'CONTENT PURPOSE' => $this->lines([
                $script->objective,
                $script->cta ? 'Intent: '.$script->cta : null,
            ]),
            'COMMUNICATION' => $this->lines([
                $persona->tone ? 'Tone: '.$persona->tone : null,
                $persona->communication_style ? 'Style: '.$persona->communication_style : null,
                $persona->audience ? 'Audience: '.$persona->audience : null,
                $persona->vocabulary ? 'Register: '.$persona->vocabulary : null,
            ]),
            'CONSTRAINTS' => [
                'Photorealistic unless the style demands otherwise, natural proportions.',
                'Avoid duplicated limbs or fingers when people appear.',
                'No visible platform UI, no watermarks, no logos unless explicitly provided.',
                'Do not render text into the image.',
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
