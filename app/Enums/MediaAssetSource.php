<?php

namespace App\Enums;

/**
 * Origem do asset (Sprint 5.5.0; uploaded na 5.5.2; composed na 5.6.1).
 */
enum MediaAssetSource: string
{
    case AiGenerated = 'ai_generated';
    case Uploaded = 'uploaded';
    case Composed = 'composed';
}
