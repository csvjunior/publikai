<?php

namespace App\Enums;

/**
 * Finalidade do asset no roteiro (Sprint 5.5.1). Default: scene.
 */
enum ContentScriptAssetPurpose: string
{
    case Cover = 'cover';
    case Scene = 'scene';
    case Product = 'product';
    case Background = 'background';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cover => 'Capa',
            self::Scene => 'Cena',
            self::Product => 'Produto',
            self::Background => 'Fundo',
            self::Other => 'Outro',
        };
    }
}
