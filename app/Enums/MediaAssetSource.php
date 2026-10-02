<?php

namespace App\Enums;

/**
 * Origem do asset (Sprint 5.5.0). Só ai_generated em uso.
 */
enum MediaAssetSource: string
{
    case AiGenerated = 'ai_generated';
    case Uploaded = 'uploaded';
}
