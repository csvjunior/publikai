<?php

namespace App\Enums;

/**
 * Origem do roteiro (Sprint 5.4). Nunca adulterável pelo request.
 */
enum ContentScriptSource: string
{
    case Manual = 'manual';
    case Ai = 'ai';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Ai => 'IA',
        };
    }
}
