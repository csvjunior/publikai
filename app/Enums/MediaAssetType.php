<?php

namespace App\Enums;

/**
 * Tipo do asset de mídia (Sprint 5.5.0). Só image em uso.
 */
enum MediaAssetType: string
{
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
}
