<?php

namespace App\Enums;

/**
 * Plataforma da conta social (Sprint 2).
 * String controlada no banco (mesma filosofia de ProductStatus).
 */
enum SocialPlatform: string
{
    case Instagram = 'instagram';
    case Tiktok = 'tiktok';
    case Youtube = 'youtube';

    public function label(): string
    {
        return match ($this) {
            self::Instagram => 'Instagram',
            self::Tiktok => 'TikTok',
            self::Youtube => 'YouTube',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Instagram => 'ai',
            self::Tiktok => 'neutral',
            self::Youtube => 'danger',
        };
    }
}
