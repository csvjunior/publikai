<?php

namespace App\Enums;

/**
 * Status da solicitação de geração de imagem (Sprint 5.5.0 async).
 */
enum ImageGenerationRequestStatus: string
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

    public function isTerminal(): bool
    {
        return $this === self::Success || $this === self::Failed;
    }
}
