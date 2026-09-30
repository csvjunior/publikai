<?php

namespace App\Enums;

/**
 * Status da análise de referência (Sprint 5.1).
 * Histórico de execução — sem archived.
 */
enum ReferenceAnalysisStatus: string
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

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Pending => 'neutral',
            self::Processing => 'info',
            self::Success => 'success',
            self::Failed => 'danger',
        };
    }
}
