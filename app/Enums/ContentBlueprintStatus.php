<?php

namespace App\Enums;

/**
 * Status do blueprint (Sprint 5.3). Mesma regra consolidada.
 */
enum ContentBlueprintStatus: string
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
