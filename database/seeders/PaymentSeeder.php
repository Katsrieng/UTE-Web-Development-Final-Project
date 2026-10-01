<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('payments')->insert([
            [
                'user_id' => 1,
                'booking_id' => 1,
                'event_booking_id' => null,
                'amount' => 150.00,
                'payment_method' => 'Cash',
                'payment_date' => '2026-10-01',
                'status' => 'Paid',
                'reference_number' => 'PAY-001',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 2,
                'booking_id' => 2,
                'event_booking_id' => null,
                'amount' => 275.00,
                'payment_method' => 'Card',
                'payment_date' => '2026-10-01',
                'status' => 'Pending',
                'reference_number' => 'PAY-002',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 3,
                'booking_id' => null,
                'event_booking_id' => 1,
                'amount' => 850.00,
                'payment_method' => 'Bank Transfer',
                'payment_date' => '2026-10-01',
                'status' => 'Paid',
                'reference_number' => 'PAY-003',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 4,
                'booking_id' => 3,
                'event_booking_id' => null,
                'amount' => 200.00,
                'payment_method' => 'Cash',
                'payment_date' => '2026-10-01',
                'status' => 'Refunded',
                'reference_number' => 'PAY-004',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
