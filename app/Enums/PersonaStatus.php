<?php

namespace App\Enums;

/**
 * Status da persona (Sprint 3).
 * Mesma filosofia dos demais status: string no banco, sem abstração genérica.
 */
enum PersonaStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Ativa',
            self::Paused => 'Pausada',
            self::Archived => 'Arquivada',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Paused => 'warning',
            self::Archived => 'neutral',
        };
    }
}
