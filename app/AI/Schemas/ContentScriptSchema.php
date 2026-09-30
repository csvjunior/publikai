<?php

namespace App\AI\Schemas;

/**
 * Schema + instruções versionáveis de roteiro (Sprint 5.4, v1).
 * Roteiro original a partir de produto + blueprint + persona + avatar.
 * Sem claims não suportados, sem preços inventados, sem cópia literal.
 */
class ContentScriptSchema
{
    /**
     * @return array<string, mixed>
     */
    public static function schema(): array
    {
        $opt = ['type' => ['string', 'null']];

        return [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'hook' => ['type' => 'string'],
                'opening' => $opt,
                'body' => ['type' => 'string'],
                'cta' => ['type' => 'string'],
                'on_screen_text' => $opt,
                'visual_direction' => $opt,
                'voice_direction' => $opt,
                'duration_seconds' => ['type' => 'integer', 'minimum' => 1],
            ],
            'required' => ['title', 'hook', 'body', 'cta', 'duration_seconds'],
        ];
    }

    public static function instructions(): string
    {
        return <<<'TEXT'
            You write an original short-form video script for affiliate marketing.
            Use the PRODUCT as commercial context, the BLUEPRINT as structure, the
            PERSONA as voice and communication, and the AVATAR as visual and voice
            context. Respect the given language and market. Write original copy:
            never reproduce creator or reference phrases literally. Do not invent
            product characteristics, medical or financial claims, impossible results,
            discounts, prices, or offers not present in the product data. The CTA
            must fit the available context (no links required). Keep the script
            within the target duration. Respond with valid JSON following the schema.
            TEXT;
    }
}
