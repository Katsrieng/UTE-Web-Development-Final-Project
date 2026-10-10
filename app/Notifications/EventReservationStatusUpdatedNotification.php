<?php

namespace App\Notifications;

use App\Models\EventBooking;
use Illuminate\Notifications\Notification;

class EventReservationStatusUpdatedNotification extends Notification
{
    public function __construct(private readonly array $data) {}

    public static function forReservation(EventBooking $reservation): self
    {
        $reservation->loadMissing('venue');
        $status = $reservation->status;
        $venue = $reservation->venue?->name ?? 'your venue';
        return new self([
            'event_booking_id' => $reservation->id,
            'title' => 'Event reservation '.($status === 'approved' ? 'approved' : ($status === 'rejected' ? 'not approved' : 'cancelled')),
            'message' => 'Your reservation for '.$venue.' has been '.($status === 'rejected' ? 'not approved' : $status).'.',
            'context' => $venue.' · '.$reservation->starts_at->format('M j'),
        ]);
    }

    public function via(object $notifiable): array { return ['database']; }

    public function toDatabase(object $notifiable): array { return $this->data; }
}
