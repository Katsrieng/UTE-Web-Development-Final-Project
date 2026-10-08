<?php

namespace App\Policies;

use App\Models\EventBooking;
use App\Models\User;

class EventBookingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, EventBooking $eventBooking): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_STAFF)
            || $eventBooking->user_id === $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->isCustomer();
    }

    public function update(User $user, EventBooking $eventBooking): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_STAFF);
    }

    public function delete(User $user, EventBooking $eventBooking): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_STAFF);
    }

    public function approve(User $user, EventBooking $eventBooking): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_STAFF)
            && $eventBooking->status === EventBooking::STATUS_PENDING;
    }

    public function reject(User $user, EventBooking $eventBooking): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_STAFF)
            && $eventBooking->status === EventBooking::STATUS_PENDING;
    }

    public function cancel(User $user, EventBooking $eventBooking): bool
    {
        $ownsBooking = $eventBooking->user_id === $user->getKey() && $user->isCustomer();
        $managesBookings = $user->hasRole(User::ROLE_ADMIN, User::ROLE_STAFF);

        return ($ownsBooking || $managesBookings) && $eventBooking->canBeCancelled();
    }
}
