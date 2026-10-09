<?php

namespace Database\Seeders;

use App\Models\EventBooking;
use App\Models\User;
use App\Models\Venue;
use DomainException;
use Illuminate\Database\Seeder;

class DemoEventSeeder extends Seeder
{
    public function run(): void
    {
        $staff = User::where('email', 'manager@utopiabay.test')->firstOrFail();
        foreach ([
            ['marker' => 'Utopia Bay demo: pending beach celebration', 'customer' => 'customer@utopiabay.test', 'venue' => 'Sunset Beach Pavilion', 'event_type' => Venue::EVENT_TYPE_PARTY, 'days' => 10, 'guests' => 40, 'approve' => false],
            ['marker' => 'Utopia Bay demo: approved garden wedding', 'customer' => 'stay@utopiabay.test', 'venue' => 'Garden Terrace', 'event_type' => Venue::EVENT_TYPE_WEDDING, 'days' => 12, 'guests' => 80, 'approve' => true],
        ] as $demo) {
            if (EventBooking::where('special_requests', $demo['marker'])->exists()) {
                continue;
            }
            $customer = User::where('email', $demo['customer'])->firstOrFail();
            $venue = Venue::where('name', $demo['venue'])->firstOrFail();
            try {
                $event = EventBooking::reserve($customer, $venue, [
                    'event_type' => $demo['event_type'],
                    'starts_at' => today()->addDays($demo['days'])->setTime(18, 0),
                    'ends_at' => today()->addDays($demo['days'])->setTime(22, 0),
                    'guest_count' => $demo['guests'],
                    'special_requests' => $demo['marker'],
                ]);
                if ($demo['approve']) {
                    $event->approve($staff, 'Demo reservation approved by resort management.');
                }
            } catch (DomainException $exception) {
                $this->command?->warn("Skipped {$demo['marker']}: {$exception->getMessage()}");
            }
        }
    }
}
