<?php

namespace App\Enums;

/**
 * Status do log de geração de IA (Sprint 5.0). Sem workflow complexo.
 */
enum AiGenerationStatus: string
{
    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';
}
