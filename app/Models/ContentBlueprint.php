<?php

namespace App\Models;

use App\Enums\ContentBlueprintSourceType;
use App\Enums\ContentBlueprintStatus;
use Database\Factories\ContentBlueprintFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Blueprint de conteúdo (Sprint 5.3): ESTRUTURA reutilizável (formato,
 * hook, sequência, CTA, estilo), não conteúdo final. Sem roteiro, sem mídia,
 * sem vínculo com Product/Persona/Avatar (entram no contexto de geração).
 */
class ContentBlueprint extends Model
{
    /** @use HasFactory<ContentBlueprintFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'content_type',
        'objective',
        'hook_pattern',
        'structure_pattern',
        'cta_pattern',
        'visual_style',
        'communication_style',
        'recommended_duration_seconds',
        'language',
        'market',
        'niche',
        'status',
        'source_type',
        'source_reference_profile_id',
        'source_reference_analysis_id',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recommended_duration_seconds' => 'integer',
            'status' => ContentBlueprintStatus::class,
            'source_type' => ContentBlueprintSourceType::class,
        ];
    }

    /**
     * @return BelongsTo<ReferenceProfile, $this>
     */
    public function sourceProfile(): BelongsTo
    {
        return $this->belongsTo(ReferenceProfile::class, 'source_reference_profile_id');
    }

    /**
     * @return BelongsTo<ReferenceAnalysis, $this>
     */
    public function sourceAnalysis(): BelongsTo
    {
        return $this->belongsTo(ReferenceAnalysis::class, 'source_reference_analysis_id');
    }

    public function isArchived(): bool
    {
        return $this->status === ContentBlueprintStatus::Archived;
    }

    public function isManual(): bool
    {
        return $this->source_type === ContentBlueprintSourceType::Manual;
    }
}
