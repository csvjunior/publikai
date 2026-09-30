<?php

namespace App\Models;

use App\Enums\IdentityProposalStatus;
use Database\Factories\IdentityProposalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Proposta de Persona + Avatar por IA (Sprint 5.2).
 * Intermediária entre análise e registros finais: IA propõe, humano revisa,
 * edita e decide aplicar. Nunca salva Persona/Avatar automaticamente.
 */
class IdentityProposal extends Model
{
    /** @use HasFactory<IdentityProposalFactory> */
    use HasFactory;

    protected $fillable = [
        'reference_profile_id',
        'reference_analysis_id',
        'status',
        'provider',
        'model',
        'persona_data',
        'avatar_data',
        'rationale',
        'error_code',
        'error_message',
        'created_by',
        'applied_persona_id',
        'applied_avatar_id',
        'started_at',
        'completed_at',
        'applied_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => IdentityProposalStatus::class,
            'persona_data' => 'array',
            'avatar_data' => 'array',
            'rationale' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ReferenceProfile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(ReferenceProfile::class, 'reference_profile_id');
    }

    /**
     * @return BelongsTo<ReferenceAnalysis, $this>
     */
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(ReferenceAnalysis::class, 'reference_analysis_id');
    }

    /**
     * @return BelongsTo<Persona, $this>
     */
    public function appliedPersona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'applied_persona_id');
    }

    /**
     * @return BelongsTo<Avatar, $this>
     */
    public function appliedAvatar(): BelongsTo
    {
        return $this->belongsTo(Avatar::class, 'applied_avatar_id');
    }

    public function isReady(): bool
    {
        return $this->status === IdentityProposalStatus::Ready;
    }
}
