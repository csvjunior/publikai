<?php

namespace App\Enums;

/**
 * Status da geração de vídeo (Sprint 5.6.0).
 * pending → starting → processing → success|failed.
 */
enum VideoGenerationRequestStatus: string
{
    case Pending = 'pending';
    case Starting = 'starting';
    case Processing = 'processing';
    case Success = 'success';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Starting => 'Iniciando',
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
