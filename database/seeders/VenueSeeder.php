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
                'location' => 'Ground Floor, East Wing',
                'capacity' => 350,
                'price' => 2500,
                'event_types' => [Venue::EVENT_TYPE_WEDDING, Venue::EVENT_TYPE_PARTY],
            ],
            [
                'name' => 'Mekong Meeting Room',
                'description' => 'A quiet meeting venue suited to workshops, presentations, and executive meetings.',
                'location' => 'Second Floor',
                'capacity' => 60,
                'price' => 450,
                'event_types' => [Venue::EVENT_TYPE_MEETING],
            ],
            [
                'name' => 'Sky Garden Terrace',
                'description' => 'An open-air rooftop venue with city views for intimate weddings and private parties.',
                'location' => 'Rooftop',
                'capacity' => 120,
                'price' => 1200,
                'event_types' => [Venue::EVENT_TYPE_WEDDING, Venue::EVENT_TYPE_PARTY],
            ],
        ];

        foreach ($venues as $venue) {
            Venue::updateOrCreate(
                ['name' => $venue['name']],
                $venue + ['images' => [], 'is_active' => true],
            );
        }
    }
}
