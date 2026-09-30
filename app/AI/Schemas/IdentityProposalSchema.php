<?php

namespace App\AI\Schemas;

/**
 * Schema + instruções versionáveis de proposta de identidade (Sprint 5.2, v1).
 * Persona e Avatar mapeiam exatamente os domínios atuais. Guardrails contra
 * inferência de atributos sensíveis de pessoas reais e estereótipos.
 */
class IdentityProposalSchema
{
    /**
     * @return array<string, mixed>
     */
    public static function schema(): array
    {
        $str = ['type' => 'string'];
        $opt = ['type' => ['string', 'null']];

        return [
            'type' => 'object',
            'properties' => [
                'persona' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => $str,
                        'language' => $str,
                        'market' => $str,
                        'audience' => $opt,
                        'personality' => $str,
                        'tone' => $str,
                        'communication_style' => $str,
                        'vocabulary' => $str,
                        'expressions' => $str,
                        'content_preferences' => $str,
                        'avoidances' => $str,
                        'default_cta_style' => $str,
                        'notes' => $opt,
                    ],
                    'required' => [
                        'name', 'language', 'market', 'personality', 'tone',
                        'communication_style', 'vocabulary', 'expressions',
                        'content_preferences', 'avoidances', 'default_cta_style',
                    ],
                ],
                'avatar' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => $str,
                        'apparent_age' => $opt,
                        'gender_presentation' => $opt,
                        'ethnicity_description' => $opt,
                        'hair' => $opt,
                        'eyes' => $opt,
                        'skin' => $opt,
                        'body_description' => $opt,
                        'default_clothing' => $opt,
                        'visual_style' => $str,
                        'preferred_scenarios' => $opt,
                        'voice_description' => $opt,
                        'language' => $str,
                        'market' => $str,
                        'reference_notes' => $opt,
                        'notes' => $opt,
                    ],
                    'required' => ['name', 'visual_style', 'language', 'market'],
                ],
                'rationale' => [
                    'type' => 'object',
                    'properties' => [
                        'persona' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'avatar' => ['type' => 'array', 'items' => ['type' => 'string']],
                    ],
                    'required' => ['persona', 'avatar'],
                ],
            ],
            'required' => ['persona', 'avatar', 'rationale'],
        ];
    }

    public static function instructions(): string
    {
        return <<<'TEXT'
            You propose a reusable creator identity (persona + avatar) for affiliate
            content operations, based ONLY on the provided reference analysis.
            Synthesize an identity coherent with the observed patterns. Respect the
            analysis language and market. Distinguish Communication DNA (persona:
            voice, tone, behavior) from Visual DNA (avatar: appearance, clothing,
            scenarios). Provide complete data when evidence supports it, null when
            it does not. Do not copy any real creator, do not imitate a specific
            person, do not reproduce phrases literally. Avoid stereotypes. NEVER
            infer real ethnicity, race, or other sensitive attributes of real people
            from references: ethnicity_description is an editorial description of
            an artificial character — use null without sufficient editorial basis,
            and never claim to represent a real person. Produce no content, only
            the identity proposal. Respond with valid JSON following the schema.
            TEXT;
    }
}
