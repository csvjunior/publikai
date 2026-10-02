<?php

namespace App\Models;

use App\Enums\ImageGenerationRequestStatus;
use Database\Factories\ImageGenerationRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Solicitação de geração de imagem (Sprint 5.5.0 async).
 * Guarda o prompt (necessário ao Job) — nunca copiado para logs ou
 * ai_generations. MediaAsset só existe após success.
 */
class ImageGenerationRequest extends Model
{
    /** @use HasFactory<ImageGenerationRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'status',
        'prompt',
        'aspect_ratio',
        'image_size',
        'mime_type',
        'provider',
        'model',
        'content_script_id',
        'purpose',
        'is_primary',
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
            'status' => ImageGenerationRequestStatus::class,
            'is_primary' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class);
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }
}
