<?php

namespace Database\Seeders;

use App\Models\MembershipType;
use App\Models\Package;
use Illuminate\Database\Seeder;

class MembershipPackageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Silver', 'price' => 30, 'discount_percentage' => 5, 'loyalty_upgrade_points' => 300, 'description' => 'Essential savings for occasional Utopia Bay stays.'],
            ['name' => 'Gold', 'price' => 60, 'discount_percentage' => 10, 'loyalty_upgrade_points' => 700, 'description' => 'More value for returning Utopia Bay guests.'],
            ['name' => 'Platinum', 'price' => 100, 'discount_percentage' => 15, 'loyalty_upgrade_points' => null, 'description' => 'Maximum savings for frequent Utopia Bay stays.'],
        ] as $plan) {
            MembershipType::firstOrCreate(['name' => $plan['name']], $plan + ['duration_months' => 12, 'status' => 'active']);
        }

        foreach (['Silver' => 'Gold', 'Gold' => 'Platinum'] as $name => $nextName) {
            $plan = MembershipType::where('name', $name)->firstOrFail();
            if ($plan->next_membership_type_id === null) {
                $plan->update(['next_membership_type_id' => MembershipType::where('name', $nextName)->firstOrFail()->id]);
            }
        }

        foreach ([
            ['name' => 'Breakfast Buffet Package', 'type' => 'buffet', 'description' => 'Breakfast buffet access for one guest. Select up to the booking guest count; charged once per selected unit.', 'price' => 15],
            ['name' => 'Romantic Getaway Package', 'type' => 'romantic', 'description' => 'A romantic room setup with a welcome amenity for one booking.', 'price' => 80],
            ['name' => 'Family Fun Package', 'type' => 'family', 'description' => 'A family activity set and kids club access for one booking.', 'price' => 50],
            ['name' => 'All-Inclusive Accommodation Package', 'type' => 'accommodation', 'description' => 'A one-time accommodation add-on for the selected booking. Room type and checkout time remain unchanged.', 'price' => 40],
        ] as $package) {
            Package::firstOrCreate(['name' => $package['name']], $package + ['status' => 'active']);
        }
    }
}
