<?php

namespace App\View;

use App\Models\Booking;

/** Presentation only: does not authorize or perform payment operations. */
class BookingStatusPresenter
{
    public static function forBooking(Booking $booking): array
    {
        $payment = $booking->payments->sortBy('id')->first();
        $active = in_array($booking->status, Booking::ACTIVE_STATUSES, true);
        $message = match ($booking->status) {
            'Confirmed' => 'Reservation confirmed',
            'Checked In' => 'Your stay is in progress',
            'Checked Out' => 'Stay completed',
            'Cancelled' => 'Reservation cancelled',
            default => 'Booking Pending',
        };
        $paymentMessage = $payment?->status ?? ($active ? 'Payment required' : 'No payment recorded');
        $label = $payment ? 'View Payment' : ($active ? 'Pay Now' : null);
        $url = $payment ? route('customer.payments.show', $payment) : ($active ? route('customer.payments.booking', $booking) : null);
        $help = '';
        if ($payment?->status === 'Pending') {
            if ($payment->payment_method === 'ABA / KHQR') {
                $paymentMessage = $booking->paymentSlip ? 'Awaiting hotel verification' : 'Complete payment submission';
                if (!$booking->paymentSlip && $active) {
                    $label = 'Continue Payment';
                    $url = route('customer.payments.booking', $booking);
                }
            } elseif (in_array($payment->payment_method, ['Cash', 'Cash at Hotel'], true)) {
                $paymentMessage = 'Pay at hotel';
                $help = 'Hotel staff will collect your payment.';
            }
        }
        return compact('payment', 'message', 'paymentMessage', 'label', 'url', 'help');
    }
}
