<?php

namespace App\Policies;

use App\Models\Court;
use App\Models\User;

class CourtPolicy
{
    public function create(User $user): bool
    {
        return $user->canManageVenues();
    }

    public function update(User $user, Court $court): bool
    {
        if (! $user->canManageVenues()) {
            return false;
        }

        return $user->canAccessCourt($court);
    }

    public function delete(User $user, Court $court): bool
    {
        if (! $user->canManageVenues()) {
            return false;
        }

        return $user->canAccessCourt($court);
    }
}
