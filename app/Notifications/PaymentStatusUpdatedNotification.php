<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Notifications\Notification;

class PaymentStatusUpdatedNotification extends Notification
{
    public function __construct(private readonly array $data) {}

    public static function forPayment(Payment $payment): self
    {
        $refunded = $payment->status === 'Refunded';
        $amount = '$'.number_format((float) $payment->amount, 2);
        $purpose = $payment->booking_id ? 'Booking #'.$payment->booking_id
            : ($payment->event_booking_id ? 'Event reservation #'.$payment->event_booking_id : 'Membership purchase');
        return new self([
            'payment_id' => $payment->id,
            'title' => $refunded ? 'Payment refunded' : 'Payment approved',
            'message' => $refunded ? 'Your payment of '.$amount.' has been refunded.' : 'Your payment for '.$purpose.' has been approved.',
            'context' => $purpose.' · '.$amount,
        ]);
    }

    public function via(object $notifiable): array { return ['database']; }

    public function toDatabase(object $notifiable): array { return $this->data; }
}
