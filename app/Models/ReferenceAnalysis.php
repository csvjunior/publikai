<?php

namespace App\Models;

use App\Enums\ReferenceAnalysisStatus;
use Database\Factories\ReferenceAnalysisFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Análise por IA de um perfil de referência (Sprint 5.1).
 * Registro imutável de execução: sucesso persiste padrões; falha persiste
 * erro sanitizado. Nunca sobrescreve o histórico.
 */
class ReferenceAnalysis extends Model
{
    /** @use HasFactory<ReferenceAnalysisFactory> */
    use HasFactory;

    protected $fillable = [
        'reference_profile_id',
        'status',
        'provider',
        'model',
        'summary',
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
        'confidence',
        'error_code',
        'error_message',
        'started_at',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReferenceAnalysisStatus::class,
            'dominant_hooks' => 'array',
            'content_structures' => 'array',
            'cta_patterns' => 'array',
            'visual_patterns' => 'array',
            'communication_patterns' => 'array',
            'audience_signals' => 'array',
            'content_angles' => 'array',
            'repeated_patterns' => 'array',
            'risks' => 'array',
            'recommendations' => 'array',
            'confidence' => 'float',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ReferenceProfile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(ReferenceProfile::class, 'reference_profile_id');
    }

    public function isSuccess(): bool
    {
        return $this->status === ReferenceAnalysisStatus::Success;
    }
}
