<?php

namespace App\Models;

use App\Enums\ReferenceContentStatus;
use Database\Factories\ReferenceContentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Conteúdo específico de um perfil de referência (Sprint 4).
 * Observações manuais de padrões (hook, estrutura, CTA, estilo) e
 * performance — base para futura análise por IA. Arquivar o perfil
 * não exclui os conteúdos (sem delete físico nesta Sprint).
 */
class ReferenceContent extends Model
{
    /** @use HasFactory<ReferenceContentFactory> */
    use HasFactory;

    protected $fillable = [
        'reference_profile_id',
        'url',
        'title',
        'content_type',
        'observed_hook',
        'observed_structure',
        'observed_cta',
        'observed_style',
        'duration_seconds',
        'performance_notes',
        'why_it_works',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'status' => ReferenceContentStatus::class,
        ];
    }

    /**
     * @return BelongsTo<ReferenceProfile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(ReferenceProfile::class, 'reference_profile_id');
    }

    public function isArchived(): bool
    {
        return $this->status === ReferenceContentStatus::Archived;
    }

    public function durationLabel(): ?string
    {
        if (is_null($this->duration_seconds)) {
            return null;
        }

        $minutes = intdiv($this->duration_seconds, 60);
        $seconds = $this->duration_seconds % 60;

        return $minutes > 0
            ? sprintf('%d:%02d', $minutes, $seconds)
            : sprintf('%ds', $seconds);
    }
}
