<?php

namespace App\AI\Schemas;

/**
 * Schema + instruções versionáveis da análise de referências (Sprint 5.1, v1).
 * A IA analisa SOMENTE dados cadastrados no Publikai — sem URLs, sem scraping.
 */
class ReferenceAnalysisSchema
{
    /**
     * @return array<string, mixed>
     */
    public static function schema(): array
    {
        $list = ['type' => 'array', 'items' => ['type' => 'string']];

        return [
            'type' => 'object',
            'properties' => [
                'summary' => ['type' => 'string', 'description' => 'Synthesis of the observed patterns.'],
                'dominant_hooks' => $list + ['description' => 'Recurring hook patterns.'],
                'content_structures' => $list + ['description' => 'Recurring content structures.'],
                'cta_patterns' => $list + ['description' => 'Recurring call-to-action patterns.'],
                'visual_patterns' => $list + ['description' => 'Recurring visual patterns.'],
                'communication_patterns' => $list + ['description' => 'Recurring communication patterns.'],
                'audience_signals' => $list + ['description' => 'Audience signals observed.'],
                'content_angles' => $list + ['description' => 'Reusable content angles.'],
                'repeated_patterns' => $list + ['description' => 'Patterns repeated across contents.'],
                'risks' => $list + ['description' => 'Risks and things to avoid.'],
                'recommendations' => $list + ['description' => 'Actionable recommendations.'],
                'confidence' => ['type' => 'number', 'description' => 'Confidence from 0 to 1 in the consistency of the identified patterns.'],
            ],
            'required' => [
                'summary', 'dominant_hooks', 'content_structures', 'cta_patterns',
                'visual_patterns', 'communication_patterns', 'audience_signals',
                'content_angles', 'repeated_patterns', 'risks', 'recommendations',
                'confidence',
            ],
        ];
    }

    public static function instructions(): string
    {
        return <<<'TEXT'
            You analyze short-form content patterns for affiliate marketing operations.
            Use ONLY the structured context provided (profile + manually observed contents).
            Never open URLs, browse, download media, or scrape. Never invent metrics,
            view counts, or causal claims — correlation is not causation.
            Identify recurring patterns and synthesize reusable structures. Separate
            observed evidence from inference. With little data, be conservative and
            report low confidence. Do not copy phrases or contents literally. Do not
            infer sensitive personal attributes of any person. Do not create personas
            or avatars. Respond with valid JSON following the schema exactly.
            TEXT;
    }
}
