<?php

namespace App\Enums;

/**
 * Origem do asset (5.5.0 ai_generated; 5.5.2 uploaded; 5.5.3/5.6.1 composed;
 * 5.6.3 merged). Composição/merge local nunca é ai_generated.
 */
enum MediaAssetSource: string
{
    case AiGenerated = 'ai_generated';
    case Uploaded = 'uploaded';
    case Composed = 'composed';
    case Merged = 'merged';
}
