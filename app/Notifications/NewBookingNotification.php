<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewBookingNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly array $bookingData) {}

    public static function forBooking(Booking $booking): self
    {
        $booking->loadMissing(['user', 'room.roomType']);

        return new self([
            'booking_id' => $booking->id,
            'booking_reference' => 'Booking #'.$booking->id,
            'customer_name' => $booking->user->name,
            'room' => trim('Room '.$booking->room->room_number.' · '.($booking->room->roomType?->name ?? '')),
            'check_in' => $booking->check_in_date,
            'check_out' => $booking->check_out_date,
            'guests' => $booking->number_of_guests,
            'total_amount' => $booking->total_amount,
            'status' => $booking->status,
        ]);
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->bookingData;
    }
}
