<?php

namespace App\Policies;

use App\Models\EventBooking;
use App\Models\User;

class EventBookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isCustomer() || $user->hasPermission('view_event_reservations');
    }

    public function view(User $user, EventBooking $eventBooking): bool
    {
        return $user->hasPermission('view_event_reservations')
            || $eventBooking->user_id === $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->isCustomer();
    }

    public function update(User $user, EventBooking $eventBooking): bool
    {
        return $user->hasPermission('edit_event_reservations');
    }

    public function delete(User $user, EventBooking $eventBooking): bool
    {
        return $user->hasPermission('delete_event_reservations');
    }

    public function approve(User $user, EventBooking $eventBooking): bool
    {
        return $user->hasPermission('approve_event_reservations')
            && $eventBooking->status === EventBooking::STATUS_PENDING;
    }

    public function reject(User $user, EventBooking $eventBooking): bool
    {
        return $user->hasPermission('reject_event_reservations')
            && $eventBooking->status === EventBooking::STATUS_PENDING;
    }

    public function cancel(User $user, EventBooking $eventBooking): bool
    {
        $ownsBooking = $eventBooking->user_id === $user->getKey() && $user->isCustomer();
        $managesBookings = $user->hasPermission('cancel_event_reservations');

        return ($ownsBooking || $managesBookings) && $eventBooking->canBeCancelled();
    }
}
