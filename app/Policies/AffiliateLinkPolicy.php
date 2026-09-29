<?php

namespace App\Policies;

use App\Models\AffiliateLink;
use App\Models\User;

/**
 * Autorização de links de afiliado (Sprint 1).
 * admin e operator criam/editam; somente admin exclui.
 */
class AffiliateLinkPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, AffiliateLink $affiliateLink): bool
    {
        return true;
    }

    public function delete(User $user, AffiliateLink $affiliateLink): bool
    {
        return $user->isAdmin();
    }
}
