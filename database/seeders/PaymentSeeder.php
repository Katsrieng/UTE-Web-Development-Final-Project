<?php

namespace Database\Seeders;

use App\Models\EventBooking;
use App\Models\Payment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        // Room-payment demo seeding is deferred to avoid inventing booking IDs.
        $eventBooking = EventBooking::whereNotNull('user_id')->orderBy('id')->first();

        if (! $eventBooking) {
            return;
        }

        Payment::firstOrCreate(
            ['reference_number' => 'DEMO-EVENT-PAYMENT'],
            [
                'user_id' => $eventBooking->user_id,
                'booking_id' => null,
                'event_booking_id' => $eventBooking->id,
                'amount' => 850.00,
                'payment_method' => 'Bank Transfer',
                'payment_date' => '2026-10-01',
                'status' => 'Paid',
            ]
        );
    }
}
