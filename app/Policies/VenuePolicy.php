<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Venue;

class VenuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_venues');
    }

    public function view(User $user, Venue $venue): bool
    {
        return $user->hasPermission('view_venues');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('manage_venues');
    }

    public function update(User $user, Venue $venue): bool
    {
        return $user->hasPermission('manage_venues');
    }

    public function delete(User $user, Venue $venue): bool
    {
        return $user->hasPermission('manage_venues');
    }
}
