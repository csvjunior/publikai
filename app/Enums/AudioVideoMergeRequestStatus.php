<?php

namespace App\Enums;

/**
 * Status do merge áudio+vídeo (Sprint 5.6.3).
 * pending → processing → success|failed.
 */
enum AudioVideoMergeRequestStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Success = 'success';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Processing => 'Processando',
            self::Success => 'Concluído',
            self::Failed => 'Falhou',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Success, self::Failed], true);
    }
}
