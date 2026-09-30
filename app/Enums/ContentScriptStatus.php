<?php

namespace App\Enums;

/**
 * Status do roteiro (Sprint 5.4). draft manual → ready → approved;
 * generating → ready|failed. archived só admin (entrada/saída).
 */
enum ContentScriptStatus: string
{
    case Draft = 'draft';
    case Generating = 'generating';
    case Ready = 'ready';
    case Approved = 'approved';
    case Failed = 'failed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Generating => 'Gerando',
            self::Ready => 'Pronto',
            self::Approved => 'Aprovado',
            self::Failed => 'Falhou',
            self::Archived => 'Arquivado',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Generating => 'info',
            self::Ready => 'success',
            self::Approved => 'ai',
            self::Failed => 'danger',
            self::Archived => 'neutral',
        };
    }
}
