<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Notification;

class BookingStatusUpdatedNotification extends Notification
{
    public function __construct(private readonly array $data) {}

    public static function forBooking(Booking $booking): self
    {
        $booking->loadMissing('room');
        return new self([
            'booking_id' => $booking->id,
            'title' => $booking->status === 'Confirmed' ? 'Booking confirmed' : 'Booking cancelled',
            'message' => 'Your booking for Room '.($booking->room?->room_number ?? $booking->room_id).' has been '.strtolower($booking->status).'.',
            'context' => 'Room '.($booking->room?->room_number ?? $booking->room_id).' · '.
                \Carbon\Carbon::parse($booking->check_in_date)->format('M j').'–'.\Carbon\Carbon::parse($booking->check_out_date)->format('M j'),
        ]);
    }

    public function via(object $notifiable): array { return ['database']; }

    public function toDatabase(object $notifiable): array { return $this->data; }
}
