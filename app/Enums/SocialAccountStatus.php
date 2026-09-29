<?php

namespace App\Enums;

/**
 * Status operacional da conta social (Sprint 2).
 * Mesma filosofia de ProductStatus; sem abstração genérica prematura.
 */
enum SocialAccountStatus: string
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
