<?php

namespace App\Services;

use App\Models\ContentScript;

/**
 * Sugestão inicial de narração (Sprint 5.6.2). Determinística, sem IA:
 * combina hook + body + CTA sem reescrever semanticamente.
 */
class NarrationTextBuilder
{
    public function build(ContentScript $script): string
    {
        $parts = array_values(array_filter([
            is_string($script->hook) ? trim($script->hook) : null,
            is_string($script->body) ? trim($script->body) : null,
            is_string($script->cta) ? trim($script->cta) : null,
        ], fn ($p) => is_string($p) && $p !== ''));

        return implode("\n\n", $parts);
    }
}
