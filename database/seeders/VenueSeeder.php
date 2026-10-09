<?php

namespace Database\Seeders;

use App\Models\Venue;
use Illuminate\Database\Seeder;

class VenueSeeder extends Seeder
{
    public function run(): void
    {
        $venues = [
            [
                'name' => 'Grand Ballroom',
                'description' => 'An elegant ballroom for wedding receptions, gala dinners, and large celebrations.',
                'location' => 'Utopia Bay, Main Resort',
                'capacity' => 350,
                'price' => 2500,
                'event_types' => [Venue::EVENT_TYPE_WEDDING, Venue::EVENT_TYPE_PARTY],
            ],
            [
                'name' => 'Sunset Beach Pavilion',
                'description' => 'An open-air coastal space for ceremonies and private celebrations.',
                'location' => 'Beachfront',
                'capacity' => 100,
                'price' => 1500,
                'event_types' => [Venue::EVENT_TYPE_WEDDING, Venue::EVENT_TYPE_PARTY],
            ],
            [
                'name' => 'Conference Room',
                'description' => 'A quiet space for presentations, workshops, and small meetings.',
                'location' => 'Utopia Bay, Meeting Wing',
                'capacity' => 60,
                'price' => 450,
                'event_types' => [Venue::EVENT_TYPE_MEETING],
            ],
            [
                'name' => 'Garden Terrace',
                'description' => 'A garden-side terrace for intimate celebrations and gatherings.',
                'location' => 'Utopia Bay, Garden',
                'capacity' => 120,
                'price' => 1200,
                'event_types' => [Venue::EVENT_TYPE_WEDDING, Venue::EVENT_TYPE_PARTY],
            ],
        ];

        foreach ($venues as $venue) {
            Venue::firstOrCreate(
                ['name' => $venue['name']],
                $venue + ['images' => [], 'is_active' => true],
            );
        }
    }
}
