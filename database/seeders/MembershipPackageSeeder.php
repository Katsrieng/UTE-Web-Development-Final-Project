<?php

namespace Database\Seeders;

use App\Models\MembershipType;
use App\Models\Package;
use Illuminate\Database\Seeder;

class MembershipPackageSeeder extends Seeder
{
    public function run(): void
    {
        MembershipType::insert([
            [
                'name' => 'Silver',
                'description' => 'Entry-level membership with a small booking discount.',
                'discount_percentage' => 5,
                'duration_months' => 12,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Gold',
                'description' => 'Mid-tier membership with a solid discount on stays.',
                'discount_percentage' => 10,
                'duration_months' => 12,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Platinum',
                'description' => 'Top-tier membership with the best resort discount.',
                'discount_percentage' => 15,
                'duration_months' => 12,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Package::insert([
            [
                'name' => 'Breakfast Buffet Package',
                'type' => 'buffet',
                'description' => 'Daily breakfast buffet for the length of the stay.',
                'price' => 15.00,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Romantic Getaway Package',
                'type' => 'romantic',
                'description' => 'Room decoration, champagne, and a couples spa session.',
                'price' => 80.00,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Family Fun Package',
                'type' => 'family',
                'description' => 'Kids club access and a family beach activity set.',
                'price' => 50.00,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'All-Inclusive Accommodation Package',
                'type' => 'accommodation',
                'description' => 'Room upgrade with late checkout and welcome drinks.',
                'price' => 40.00,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
