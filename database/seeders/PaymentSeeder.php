<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\User;
use App\Services\BookingService;
use App\Services\PaymentService;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $payments = app(PaymentService::class);
        $bookings = app(BookingService::class);
        $staff = User::where('email', 'staff@utopiabay.test')->firstOrFail();

        foreach (DemoBookingSeeder::BOOKINGS as $marker => $details) {
            $booking = Booking::where('special_request', $marker)
                ->whereHas('user', fn ($query) => $query->where('email', $details['customer']))
                ->first();
            if (! $booking) {
                continue;
            }

            $payment = $booking->payments()->orderBy('id')->first();
            if (! $payment) {
                if (! in_array($booking->status, Booking::ACTIVE_STATUSES, true)) {
                    $this->command?->warn("Skipped payment for {$marker}: booking is no longer payable.");
                    continue;
                }
                $payment = $payments->payBooking($booking->user, $booking->id, [
                    'payment_method' => $details['payment_method'],
                ]);
            }
            if ($details['payment_method'] === 'Card' && $payment->status === 'Paid') {
                if ($details['final_status'] === 'Checked In' && $booking->fresh()->status === 'Confirmed') {
                    $bookings->transition((string) $booking->id, 'Checked In', $staff);
                }
                if ($details['final_status'] === 'Checked Out') {
                    if ($booking->fresh()->status === 'Confirmed') {
                        $bookings->transition((string) $booking->id, 'Checked In', $staff);
                    }
                    if ($booking->fresh()->status === 'Checked In') {
                        $bookings->transition((string) $booking->id, 'Checked Out', $staff);
                    }
                }
            }
        }
    }
}
