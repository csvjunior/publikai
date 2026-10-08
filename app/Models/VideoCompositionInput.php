<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Input de composição com snapshot (Sprint 5.6.1).
 * Vídeo: trim opcional (ms). Imagem: duração do segmento (ms).
 */
class VideoCompositionInput extends Model
{
    protected $fillable = [
        'video_composition_request_id',
        'media_asset_id',
        'position',
        'trim_start_ms',
        'trim_end_ms',
        'image_duration_ms',
    ];

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class);
    }

    /**
     * @return BelongsTo<VideoCompositionRequest, $this>
     */
    public function composition(): BelongsTo
    {
        return $this->belongsTo(VideoCompositionRequest::class, 'video_composition_request_id');
    }
}
