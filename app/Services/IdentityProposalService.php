<?php

namespace App\Services;

use App\AI\Exceptions\AiProviderException;
use App\AI\Schemas\IdentityProposalSchema;
use App\Enums\IdentityProposalStatus;
use App\Enums\ReferenceAnalysisStatus;
use App\Exceptions\AnalysisInProgressException;
use App\Exceptions\InsufficientAnalysisContextException;
use App\Models\Avatar;
use App\Models\IdentityProposal;
use App\Models\Persona;
use App\Models\ReferenceProfile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Propostas de identidade por IA (Sprint 5.2).
 * IA propõe a partir da latest successful analysis; humano revisa, edita e
 * aplica. Aplicar cria Persona + Avatar reais em transação (idempotente).
 * Execução síncrona com o orçamento seguro de timeout da IA.
 */
class IdentityProposalService
{
    public function __construct(protected AiService $ai) {}

    /**
     * @throws AnalysisInProgressException
     * @throws InsufficientAnalysisContextException
     */
    public function generate(ReferenceProfile $profile, ?int $createdBy = null): IdentityProposal
    {
        if ($profile->identityProposals()
            ->whereIn('status', [IdentityProposalStatus::Pending, IdentityProposalStatus::Processing])
            ->exists()) {
            throw new AnalysisInProgressException('Já existe uma proposta em processamento.');
        }

        $analysis = $profile->referenceAnalyses()
            ->where('status', ReferenceAnalysisStatus::Success)
            ->latest()
            ->first();

        if (! $analysis || ! $this->aiConfigured()) {
            throw new InsufficientAnalysisContextException(
                'Conclua uma análise por IA desta referência antes de gerar Persona e Avatar.'
            );
        }

        $proposal = IdentityProposal::create([
            'reference_profile_id' => $profile->id,
            'reference_analysis_id' => $analysis->id,
            'status' => IdentityProposalStatus::Pending,
            'provider' => config('ai.provider', 'google'),
            'model' => (string) config('ai.google.model'),
            'created_by' => $createdBy,
            'started_at' => now(),
        ]);

        $proposal->update(['status' => IdentityProposalStatus::Processing]);

        try {
            $result = $this->ai->generate(
                operation: 'identity_proposal',
                instructions: IdentityProposalSchema::instructions(),
                input: $this->buildInput($profile, $analysis),
                schema: IdentityProposalSchema::schema(),
            );
        } catch (AiProviderException $e) {
            $proposal->update([
                'status' => IdentityProposalStatus::Failed,
                'error_code' => $e->errorCode,
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            return $proposal;
        }

        $data = $this->validatedData($result->data);

        if ($data === null) {
            $proposal->update([
                'status' => IdentityProposalStatus::Failed,
                'error_code' => 'schema_mismatch',
                'error_message' => 'Resposta da IA fora do formato esperado.',
                'completed_at' => now(),
            ]);

            return $proposal;
        }

        $proposal->update(array_merge($data, [
            'status' => IdentityProposalStatus::Ready,
            'completed_at' => now(),
        ]));

        return $proposal;
    }

    /**
     * Revisão humana: altera SOMENTE os dados da proposta.
     *
     * @param  array<string, mixed>  $persona
     * @param  array<string, mixed>  $avatar
     */
    public function revise(IdentityProposal $proposal, array $persona, array $avatar): IdentityProposal
    {
        $this->ensureReady($proposal);

        $proposal->update([
            'persona_data' => $persona,
            'avatar_data' => $avatar,
        ]);

        return $proposal;
    }

    /**
     * Aplica a proposta: cria Persona + Avatar reais (status active) em
     * transação, com lock contra duplo clique. Idempotente.
     */
    public function apply(IdentityProposal $proposal): IdentityProposal
    {
        return DB::transaction(function () use ($proposal) {
            $locked = IdentityProposal::whereKey($proposal->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== IdentityProposalStatus::Ready) {
                return $locked;
            }

            $persona = Persona::create(array_merge(
                Arr::only($locked->persona_data ?? [], (new Persona)->getFillable()),
                ['status' => 'active']
            ));

            $avatar = Avatar::create(array_merge(
                Arr::only($locked->avatar_data ?? [], (new Avatar)->getFillable()),
                ['status' => 'active']
            ));

            $locked->update([
                'applied_persona_id' => $persona->id,
                'applied_avatar_id' => $avatar->id,
                'applied_at' => now(),
                'status' => IdentityProposalStatus::Applied,
            ]);

            return $locked;
        });
    }

    public function discard(IdentityProposal $proposal): IdentityProposal
    {
        $this->ensureReady($proposal);

        $proposal->update(['status' => IdentityProposalStatus::Discarded]);

        return $proposal;
    }

    protected function ensureReady(IdentityProposal $proposal): void
    {
        abort_unless($proposal->status === IdentityProposalStatus::Ready, 409, 'Proposta não está pronta para esta ação.');
    }

    protected function aiConfigured(): bool
    {
        return (bool) config('ai.google.enabled') && (string) config('ai.google.auth_key') !== '';
    }

    /**
     * Monta o input só com dados cadastrados (análise + perfil, sem URLs,
     * sem notes internas, sem ids).
     */
    protected function buildInput(ReferenceProfile $profile, $analysis): string
    {
        $lines = ['REFERENCE ANALYSIS'];

        foreach ([
            'Profile' => $profile->name,
            'Platform' => $profile->platform->label(),
            'Language' => $profile->language,
            'Market' => $profile->market,
            'Niche' => $profile->niche,
            'Summary' => $analysis->summary,
            'Confidence' => $analysis->confidence,
        ] as $label => $value) {
            if ($value !== null && $value !== '') {
                $lines[] = $label.': '.$value;
            }
        }

        foreach ([
            'Hooks' => $analysis->dominant_hooks,
            'Structures' => $analysis->content_structures,
            'CTAs' => $analysis->cta_patterns,
            'Visual' => $analysis->visual_patterns,
            'Communication' => $analysis->communication_patterns,
            'Audience' => $analysis->audience_signals,
            'Angles' => $analysis->content_angles,
            'Repeated' => $analysis->repeated_patterns,
            'Risks' => $analysis->risks,
            'Recommendations' => $analysis->recommendations,
        ] as $label => $items) {
            if (is_array($items) && $items !== []) {
                $lines[] = $label.': '.implode(' | ', array_filter($items, 'is_string'));
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Valida a resposta (persona/avatar/rationale + códigos de idioma e mercado).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    protected function validatedData(array $data): ?array
    {
        if (! isset($data['persona'], $data['avatar'], $data['rationale'])
            || ! is_array($data['persona']) || ! is_array($data['avatar']) || ! is_array($data['rationale'])) {
            return null;
        }

        foreach (['name', 'language', 'market'] as $field) {
            if (empty($data['persona'][$field]) || ! is_string($data['persona'][$field])) {
                return null;
            }
        }

        foreach (['name', 'language', 'market'] as $field) {
            if (empty($data['avatar'][$field]) || ! is_string($data['avatar'][$field])) {
                return null;
            }
        }

        $languages = array_keys(config('locale-options.languages'));
        $markets = array_keys(config('locale-options.markets'));

        if (! in_array($data['persona']['language'], $languages, true)
            || ! in_array($data['persona']['market'], $markets, true)
            || ! in_array($data['avatar']['language'], $languages, true)
            || ! in_array($data['avatar']['market'], $markets, true)) {
            return null;
        }

        foreach (['persona', 'avatar'] as $key) {
            if (! isset($data['rationale'][$key]) || ! is_array($data['rationale'][$key])) {
                return null;
            }
        }

        return [
            'persona_data' => $data['persona'],
            'avatar_data' => $data['avatar'],
            'rationale' => [
                'persona' => array_values(array_filter($data['rationale']['persona'], 'is_string')),
                'avatar' => array_values(array_filter($data['rationale']['avatar'], 'is_string')),
            ],
        ];
    }
}
