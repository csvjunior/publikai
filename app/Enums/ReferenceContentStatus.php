<?php

namespace App\Enums;

/**
 * Status do conteúdo de referência (Sprint 4).
 * Mesma filosofia dos demais status: string no banco, sem abstração genérica.
 */
enum ReferenceContentStatus: string
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
