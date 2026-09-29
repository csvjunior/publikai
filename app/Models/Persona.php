<?php

namespace App\Models;

use App\Enums\PersonaStatus;
use Database\Factories\PersonaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Persona: identidade de comunicação reutilizável (Sprint 3).
 * Personalidade, linguagem, tom e diretrizes de texto/fala — apenas
 * cadastro e organização, sem interpretação por IA.
 */
class Persona extends Model
{
    /** @use HasFactory<PersonaFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'language',
        'market',
        'audience',
        'personality',
        'tone',
        'communication_style',
        'vocabulary',
        'expressions',
        'content_preferences',
        'avoidances',
        'default_cta_style',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PersonaStatus::class,
        ];
    }

    public function isArchived(): bool
    {
        return $this->status === PersonaStatus::Archived;
    }
}
