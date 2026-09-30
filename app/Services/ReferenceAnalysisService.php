<?php

namespace App\Services;

use App\AI\Exceptions\AiProviderException;
use App\AI\Schemas\ReferenceAnalysisSchema;
use App\Enums\ReferenceAnalysisStatus;
use App\Enums\ReferenceContentStatus;
use App\Enums\ReferenceProfileStatus;
use App\Exceptions\AnalysisInProgressException;
use App\Exceptions\InsufficientAnalysisContextException;
use App\Models\ReferenceAnalysis;
use App\Models\ReferenceContent;
use App\Models\ReferenceProfile;
use Illuminate\Support\Collection;

/**
 * Análise por IA de perfis de referência (Sprint 5.1).
 * Execução síncrona; falha do provider vira failed sanitizado sem
 * derrubar a página. Cada tentativa cria um registro (histórico imutável).
 * Usa SOMENTE dados cadastrados — sem URLs, sem scraping.
 */
class ReferenceAnalysisService
{
    /**
     * @var string[]
     */
    private const ARRAY_FIELDS = [
        'dominant_hooks',
        'content_structures',
        'cta_patterns',
        'visual_patterns',
        'communication_patterns',
        'audience_signals',
        'content_angles',
        'repeated_patterns',
        'risks',
        'recommendations',
    ];

    public function __construct(protected AiService $ai) {}

    /**
     * @throws AnalysisInProgressException
     * @throws InsufficientAnalysisContextException
     */
    public function analyze(ReferenceProfile $profile): ReferenceAnalysis
    {
        if ($profile->referenceAnalyses()
            ->whereIn('status', [ReferenceAnalysisStatus::Pending, ReferenceAnalysisStatus::Processing])
            ->exists()) {
            throw new AnalysisInProgressException;
        }

        if ($profile->status !== ReferenceProfileStatus::Active) {
            throw new InsufficientAnalysisContextException;
        }

        $contents = $profile->referenceContents()
            ->where('status', ReferenceContentStatus::Active->value)
            ->orderBy('id')
            ->get();

        if ($contents->isEmpty()) {
            throw new InsufficientAnalysisContextException;
        }

        $analysis = ReferenceAnalysis::create([
            'reference_profile_id' => $profile->id,
            'status' => ReferenceAnalysisStatus::Pending,
            'provider' => config('ai.provider', 'google'),
            'model' => (string) config('ai.google.model'),
            'started_at' => now(),
        ]);

        $analysis->update(['status' => ReferenceAnalysisStatus::Processing]);

        try {
            $result = $this->ai->generate(
                operation: 'reference_analysis',
                instructions: ReferenceAnalysisSchema::instructions(),
                input: $this->buildInput($profile, $contents),
                schema: ReferenceAnalysisSchema::schema(),
            );
        } catch (AiProviderException $e) {
            $analysis->update([
                'status' => ReferenceAnalysisStatus::Failed,
                'error_code' => $e->errorCode,
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            return $analysis;
        }

        $data = $this->validatedData($result->data);

        if ($data === null) {
            $analysis->update([
                'status' => ReferenceAnalysisStatus::Failed,
                'error_code' => 'schema_mismatch',
                'error_message' => 'Resposta da IA fora do formato esperado.',
                'completed_at' => now(),
            ]);

            return $analysis;
        }

        $analysis->update(array_merge($data, [
            'status' => ReferenceAnalysisStatus::Success,
            'completed_at' => now(),
        ]));

        return $analysis;
    }

    /**
     * Monta o input só com dados cadastrados (sem notes, sem ids, sem URLs
     * como instrução de navegação — URLs aparecem apenas como referência
     * textual do conteúdo, sem pedir acesso).
     *
     * @param  Collection<int, ReferenceContent>  $contents
     */
    protected function buildInput(ReferenceProfile $profile, $contents): string
    {
        $lines = ['PROFILE'];

        foreach ([
            'Name' => $profile->name,
            'Platform' => $profile->platform->label(),
            'Username' => $profile->username ? '@'.$profile->username : null,
            'Language' => $profile->language,
            'Market' => $profile->market,
            'Niche' => $profile->niche,
            'Reason' => $profile->reason,
        ] as $label => $value) {
            if ($value) {
                $lines[] = $label.': '.$value;
            }
        }

        $lines[] = '';
        $lines[] = 'OBSERVED CONTENTS ('.$contents->count().')';

        foreach ($contents as $i => $content) {
            $lines[] = '';
            $lines[] = 'Content '.($i + 1).':';

            foreach ([
                'Title' => $content->title,
                'Type' => $content->content_type ? (config('references.content_types')[$content->content_type] ?? $content->content_type) : null,
                'Hook' => $content->observed_hook,
                'Structure' => $content->observed_structure,
                'CTA' => $content->observed_cta,
                'Style' => $content->observed_style,
                'Duration' => $content->durationLabel(),
                'Performance' => $content->performance_notes,
                'Why it works' => $content->why_it_works,
            ] as $label => $value) {
                if ($value) {
                    $lines[] = '  '.$label.': '.$value;
                }
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Valida tipos da resposta (summary string, listas de strings,
     * confidence 0..1). Retorna null se inválido.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    protected function validatedData(array $data): ?array
    {
        if (! isset($data['summary']) || ! is_string($data['summary']) || trim($data['summary']) === '') {
            return null;
        }

        $validated = ['summary' => $data['summary']];

        foreach (self::ARRAY_FIELDS as $field) {
            if (! isset($data[$field]) || ! is_array($data[$field])) {
                return null;
            }

            foreach ($data[$field] as $item) {
                if (! is_string($item)) {
                    return null;
                }
            }

            $validated[$field] = array_values($data[$field]);
        }

        if (! isset($data['confidence']) || ! is_numeric($data['confidence'])) {
            return null;
        }

        $confidence = (float) $data['confidence'];

        if ($confidence < 0 || $confidence > 1) {
            return null;
        }

        $validated['confidence'] = $confidence;

        return $validated;
    }
}
