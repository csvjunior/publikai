<?php

namespace App\Policies;

use App\Enums\ContentBlueprintStatus;
use App\Models\ContentBlueprint;
use App\Models\User;

/**
 * Autorização de blueprints (Sprint 5.3).
 * Mesma regra consolidada: entrar/sair de `archived` só admin.
 */
class ContentBlueprintPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ContentBlueprint $blueprint): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ContentBlueprint $blueprint): bool
    {
        return true;
    }

    public function updateStatus(User $user, ContentBlueprint $blueprint, string $targetStatus): bool
    {
        $involvesArchived = $targetStatus === ContentBlueprintStatus::Archived->value
            || $blueprint->status === ContentBlueprintStatus::Archived;

        if (! $involvesArchived) {
            return true;
        }

        return $user->isAdmin();
    }
}
