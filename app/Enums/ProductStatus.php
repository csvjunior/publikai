<?php

namespace App\Enums;

/**
 * Status do produto (Sprint 1).
 *
 * String controlada no banco + enum no PHP (mesmo padrão de UserRole):
 * compatibilidade MariaDB/MySQL e evolução sem alteração destrutiva.
 */
enum ProductStatus: string
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
