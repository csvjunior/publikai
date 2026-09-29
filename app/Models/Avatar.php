<?php

namespace App\Models;

use App\Enums\AvatarStatus;
use Database\Factories\AvatarFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Avatar: identidade visual reutilizável (Sprint 3).
 * Características físicas, vestuário, cenário, voz futura — tudo entrada
 * manual da equipe, sem inferência ou geração.
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
}
