<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Venue;

class VenuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_STAFF);
    }

    public function view(User $user, Venue $venue): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_STAFF);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_STAFF);
    }

    public function update(User $user, Venue $venue): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_STAFF);
    }

    public function delete(User $user, Venue $venue): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_STAFF);
    }
}
