<?php

namespace App\Enums;

/**
 * Etapa atual da produção (Sprint 5.6.5).
 * Nomes de domínio, nunca de Jobs/Providers.
 */
enum ContentProductionStep: string
{
    case Preparing = 'preparing';
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case Finalizing = 'finalizing';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Preparing => 'Preparando visual',
            self::Image => 'Preparando visual',
            self::Video => 'Criando vídeo',
            self::Audio => 'Criando narração',
            self::Finalizing => 'Finalizando vídeo',
            self::Completed => 'Vídeo pronto',
        };
    }
}
