<?php

namespace App\Enums;

/**
 * Tipo de conteúdo pretendido (Sprint 5.6.4).
 * Definido na criação; linhas antigas (null) usam inferência no view-model.
 */
enum ContentType: string
{
    case Video = 'video';
    case Image = 'image';

    public function label(): string
    {
        return match ($this) {
            self::Video => 'Vídeo',
            self::Image => 'Imagem',
        };
    }
}
