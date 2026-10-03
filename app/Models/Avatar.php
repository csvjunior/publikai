<?php

namespace App\Models;

use App\Enums\AvatarStatus;
use Database\Factories\AvatarFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Avatar: identidade visual reutilizável (Sprint 3).
 * Características físicas, vestuário, cenário, voz futura — tudo entrada
 * manual da equipe, sem inferência ou geração.
 * Imagem de referência ativa opcional (Sprint 5.5.2): um MediaAsset
 * `uploaded` tratado só como auxílio de consistência visual do personagem
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
        'reference_media_asset_id',
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
     * @return BelongsTo<MediaAsset, $this>
     */
    public function referenceImage(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'reference_media_asset_id');
    }

    public function hasReferenceImage(): bool
    {
        return $this->reference_media_asset_id !== null;
    }
}
