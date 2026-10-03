<?php

namespace App\Support;

/**
 * Formatação amigável de tamanho de arquivo (Sprint 5.5.2).
 * Apresentação pura (B/KB/MB), sem intl, sem lógica de domínio.
 */
class FileSize
{
    public static function format(?int $bytes): ?string
    {
        if ($bytes === null || $bytes < 0) {
            return null;
        }

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return rtrim(rtrim(number_format($bytes / 1024, 1), '0'), '.').' KB';
        }

        return rtrim(rtrim(number_format($bytes / (1024 * 1024), 1), '0'), '.').' MB';
    }
}
