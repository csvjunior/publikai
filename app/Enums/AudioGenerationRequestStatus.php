<?php

namespace App\Enums;

/**
 * Status da geração de áudio (Sprint 5.6.2).
 * pending → processing → success|failed.
 */
enum AudioGenerationRequestStatus: string
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
            self::Success => 'Concluída',
            self::Failed => 'Falhou',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Success, self::Failed], true);
    }
}
