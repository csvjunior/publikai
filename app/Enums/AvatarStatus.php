<?php

namespace App\Enums;

/**
 * Status do avatar (Sprint 3).
 * Mesma filosofia dos demais status: string no banco, sem abstração genérica.
 */
enum AvatarStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Ativo',
            self::Paused => 'Pausado',
            self::Archived => 'Arquivado',
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
