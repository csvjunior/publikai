<?php

namespace App\Enums;

/**
 * Status da proposta de identidade (Sprint 5.2).
 * pending → processing → ready|failed; ready → applied|discarded.
 * failed preserva histórico. Aplicada não reexecuta.
 */
enum IdentityProposalStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';
    case Applied = 'applied';
    case Discarded = 'discarded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Processing => 'Processando',
            self::Ready => 'Pronta',
            self::Failed => 'Falhou',
            self::Applied => 'Aplicada',
            self::Discarded => 'Descartada',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Pending => 'neutral',
            self::Processing => 'info',
            self::Ready => 'success',
            self::Failed => 'danger',
            self::Applied => 'ai',
            self::Discarded => 'neutral',
        };
    }
}
