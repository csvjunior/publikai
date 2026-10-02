<?php

namespace App\Enums;

/**
 * Status do asset (Sprint 5.5.0). Falhas ficam só em ai_generations —
 * MediaAsset só existe com arquivo válido.
 */
enum MediaAssetStatus: string
{
    case Ready = 'ready';
    case Failed = 'failed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Ready => 'Pronto',
            self::Failed => 'Falhou',
            self::Archived => 'Arquivado',
        };
    }
}
