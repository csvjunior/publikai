<?php

namespace App\Enums;

/**
 * Política de duração do merge (Sprint 5.6.3).
 * Só video_master nesta Sprint: output dura o vídeo; áudio menor deixa
 * silêncio, áudio maior é cortado. Persistida para decisão explícita.
 */
enum AudioVideoDurationPolicy: string
{
    case VideoMaster = 'video_master';

    public function label(): string
    {
        return match ($this) {
            self::VideoMaster => 'Vídeo principal',
        };
    }
}
