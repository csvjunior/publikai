<?php

namespace App\Models;

use App\Enums\AiGenerationStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * Registro sanitizado de uma geração de IA (Sprint 5.0).
 * Sem credenciais, sem prompts completos, sem corpo de resposta.
 */
class AiGeneration extends Model
{
    protected $fillable = [
        'provider',
        'model',
        'operation',
        'status',
        'input_tokens',
        'output_tokens',
        'estimated_cost_usd',
        'duration_ms',
        'external_request_id',
        'error_code',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AiGenerationStatus::class,
            'estimated_cost_usd' => 'decimal:6',
            'metadata' => 'array',
        ];
    }
}
