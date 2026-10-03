<?php

namespace App\Models;

use App\Enums\AvatarStatus;
use Database\Factories\AvatarFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Avatar: identidade visual reutilizável (Sprint 3).
 * Características físicas, vestuário, cenário, voz futura — tudo entrada
 * manual da equipe, sem inferência ou geração.
 * Referências visuais aprovadas (Sprints 5.5.2/5.5.3): MediaAssets
 * `uploaded` tratados só como auxílio de consistência visual do personagem
 * artificial — nunca identidade verificada de pessoa real.
 */
class Avatar extends Model
{
    /** @use HasFactory<AvatarFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'apparent_age',
        'gender_presentation',
        'ethnicity_description',
        'hair',
        'eyes',
        'skin',
        'body_description',
        'default_clothing',
        'visual_style',
        'preferred_scenarios',
        'voice_description',
        'language',
        'market',
        'reference_notes',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AvatarStatus::class,
        ];
    }

    public function isArchived(): bool
    {
        return $this->status === AvatarStatus::Archived;
    }

    /**
     * Referências visuais aprovadas (Sprint 5.5.3), ordenação estável.
     *
     * @return BelongsToMany<MediaAsset, $this>
     */
    public function referenceImages(): BelongsToMany
    {
        return $this->belongsToMany(MediaAsset::class, 'avatar_reference_media_assets')
            ->withPivot(['is_primary', 'position'])
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function primaryReferenceImage(): ?MediaAsset
    {
        return $this->referenceImages()->wherePivot('is_primary', true)->first();
    }

    public function referencesCount(): int
    {
        return $this->referenceImages()->count();
    }
}
