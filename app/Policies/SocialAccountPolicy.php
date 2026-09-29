<?php

namespace App\Policies;

use App\Enums\SocialAccountStatus;
use App\Models\SocialAccount;
use App\Models\User;

/**
 * Autorização de contas sociais (Sprint 2).
 * Mesma regra de Product: qualquer transição para ou a partir de
 * `archived` exige admin; operators alternam apenas entre active e paused.
 */
class SocialAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SocialAccount $socialAccount): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, SocialAccount $socialAccount): bool
    {
        return true;
    }

    /**
     * Autoriza uma mudança de status. Entrar ou sair de `archived` é
     * exclusivo de admin; as demais transições são livres (ambos os papéis).
     */
    public function updateStatus(User $user, SocialAccount $socialAccount, string $targetStatus): bool
    {
        $involvesArchived = $targetStatus === SocialAccountStatus::Archived->value
            || $socialAccount->status === SocialAccountStatus::Archived;

        if (! $involvesArchived) {
            return true;
        }

        return $user->isAdmin();
    }
}
