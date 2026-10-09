<?php

namespace App\Enums;

/**
 * Status da produção coordenada (Sprint 5.6.5).
 * pending → processing → success|failed. Sem cancelled (sem UX).
 */
enum ContentProductionStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Success = 'success';
    case Failed = 'failed';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Success, self::Failed], true);
    }
}
