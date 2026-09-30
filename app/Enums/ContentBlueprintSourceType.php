<?php

namespace App\Enums;

/**
 * Origem do blueprint (Sprint 5.3). Manual é o único fluxo real;
 * ai_assisted fica preparado para a evolução com IA.
 */
enum ContentBlueprintSourceType: string
{
    case Manual = 'manual';
    case AiAssisted = 'ai_assisted';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::AiAssisted => 'Assistido por IA',
        };
    }
}
