<?php

namespace App\Models;

use App\Enums\AudioGenerationRequestStatus;
use Database\Factories\AudioGenerationRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Solicitação de narração por voz (Sprint 5.6.2, TTS).
 * Guarda o texto (necessário ao Job) — nunca copiado para logs ou
 * ai_generations. MediaAsset só existe após success.
 */
class AudioGenerationRequest extends Model
{
    /** @use HasFactory<AudioGenerationRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'status',
        'text',
        'voice',
        'language',
        'style',
        'provider',
        'model',
        'content_script_id',
        'content_production_id',
        'media_asset_id',
        'error_code',
        'error_message',
        'created_by',
        'started_at',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AudioGenerationRequestStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }
}
