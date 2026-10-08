<?php

namespace App\Http\Requests;

use App\Models\User;

/** Reuse the customer request's date, venue, event type and capacity rules. */
class UpdateEventReservationRequest extends StoreEventReservationRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('edit_event_reservations') ?? false;
    }
}
