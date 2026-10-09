<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Membership;
use App\Models\MembershipPurchase;
use App\Models\MembershipType;
use App\Models\Package;
use App\Models\Room;
use App\Models\User;
use App\Services\BookingService;
use App\Services\PaymentService;
use Illuminate\Database\Seeder;
use Illuminate\Validation\ValidationException;

class DemoBookingSeeder extends Seeder
{
    public const BOOKINGS = [
        'Utopia Bay demo: confirmed arrival' => ['customer' => 'customer@utopiabay.test', 'room' => 'UB-101', 'arrival' => 2, 'departure' => 4, 'guests' => 2, 'package' => 'Breakfast Buffet Package', 'quantity' => 2, 'payment_method' => 'Card', 'final_status' => 'Confirmed'],
        'Utopia Bay demo: pending stay' => ['customer' => 'customer@utopiabay.test', 'room' => 'UB-102', 'arrival' => 5, 'departure' => 7, 'guests' => 2, 'package' => null, 'quantity' => 0, 'payment_method' => 'Cash at Hotel', 'final_status' => 'Pending'],
        'Utopia Bay demo: checked-in stay' => ['customer' => 'stay@utopiabay.test', 'room' => 'UB-201', 'arrival' => 0, 'departure' => 2, 'guests' => 3, 'package' => 'Family Fun Package', 'quantity' => 1, 'payment_method' => 'Card', 'final_status' => 'Checked In'],
        'Utopia Bay demo: completed stay' => ['customer' => 'history@utopiabay.test', 'room' => 'UB-202', 'arrival' => -2, 'departure' => -1, 'guests' => 2, 'package' => null, 'quantity' => 0, 'payment_method' => 'Card', 'final_status' => 'Checked Out'],
    ];

    public function run(): void
    {
        $customer = User::where('email', 'customer@utopiabay.test')->firstOrFail();
        if (! Membership::where('user_id', $customer->id)->where('status', 'active')->whereDate('end_date', '>=', today())->exists()
            && ! MembershipPurchase::where('user_id', $customer->id)->where('status', 'pending')->exists()) {
            $silver = MembershipType::where('name', 'Silver')->firstOrFail();
            if ($silver->status === 'active' && (float) $silver->price > 0 && $silver->duration_months == 12) {
                app(PaymentService::class)->purchaseMembership($customer, $silver->id, ['payment_method' => 'Card']);
            }
        }

        $service = app(BookingService::class);
        foreach (self::BOOKINGS as $marker => $details) {
            $guest = User::where('email', $details['customer'])->firstOrFail();
            if (Booking::where('user_id', $guest->id)->where('special_request', $marker)->exists()) {
                continue;
            }

            $room = Room::where('room_number', $details['room'])->firstOrFail();
            $selection = [];
            if ($details['package']) {
                $package = Package::where('name', $details['package'])->firstOrFail();
                $selection[] = ['package_id' => $package->id, 'quantity' => $details['quantity']];
            }

            try {
                $service->create([
                    'user_id' => $guest->id,
                    'room_id' => $room->id,
                    'check_in_date' => today()->addDays($details['arrival'])->toDateString(),
                    'check_out_date' => today()->addDays($details['departure'])->toDateString(),
                    'number_of_guests' => $details['guests'],
                    'special_request' => $marker,
                    'packages' => $selection,
                ]);
            } catch (ValidationException $exception) {
                $this->command?->warn("Skipped {$marker}: ".implode(' ', $exception->validator->errors()->all()));
            }
        }
    }
}
