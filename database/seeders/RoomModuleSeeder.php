<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Seeder;

class RoomModuleSeeder extends Seeder
{
    public function run(): void
    {
        $facilities = [
            ['name' => 'High-Speed Wi-Fi', 'description' => 'Complimentary wireless internet throughout the room.', 'status' => 'open'],
            ['name' => 'Air Conditioning', 'description' => 'Individually controlled climate cooling.', 'status' => 'open'],
            ['name' => 'Mini Refrigerator', 'description' => 'A compact refrigerator for drinks and snacks.', 'status' => 'open'],
            ['name' => 'Flat Screen TV', 'description' => 'Smart television with international channels.', 'status' => 'open'],
            ['name' => 'Work Desk', 'description' => 'A comfortable workspace with convenient charging points.', 'status' => 'open'],
            ['name' => 'In-Room Safe', 'description' => 'Secure storage for valuables and travel documents.', 'status' => 'open'],
        ];

        $facilityModels = [];

        foreach ($facilities as $facility) {
            $facilityModels[$facility['name']] = Facility::firstOrCreate(
                ['name' => $facility['name']],
                $facility
            );
        }

        $roomTypes = [
            [
                'name' => 'Standard Single',
                'description' => 'A calm, practical room for solo travellers with a dedicated work area and warm city views.',
                'base_price' => 35.00,
                'capacity' => 1,
                'bed_type' => '1 Single Bed',
            ],
            [
                'name' => 'Deluxe Suite',
                'description' => 'A spacious king suite with a relaxing lounge corner and refined Cambodian-inspired details.',
                'base_price' => 75.00,
                'capacity' => 2,
                'bed_type' => '1 King Bed',
            ],
            [
                'name' => 'Executive Family Suite',
                'description' => 'A bright family suite with two queen beds, generous storage, and a comfortable seating area.',
                'base_price' => 120.00,
                'capacity' => 4,
                'bed_type' => '2 Queen Beds',
            ],
        ];

        $roomTypeModels = [];

        foreach ($roomTypes as $type) {
            $roomTypeModels[$type['name']] = RoomType::firstOrCreate(
                ['name' => $type['name']],
                $type
            );
        }

        $rooms = [
            [
                'room_type' => 'Standard Single',
                'room_number' => '101',
                'floor' => 1,
                'price_per_night' => 35.00,
                'status' => 'available',
                'description' => 'A peaceful single room with a comfortable bed, writing desk, and warm natural finishes.',
                'image' => 'images/rooms/standard-single.jpg',
                'facilities' => ['High-Speed Wi-Fi', 'Air Conditioning', 'Flat Screen TV', 'Work Desk'],
            ],
            [
                'room_type' => 'Deluxe Suite',
                'room_number' => '204',
                'floor' => 2,
                'price_per_night' => 85.00,
                'status' => 'available',
                'description' => 'An elegant king suite with extra living space, a city view, and upgraded guest amenities.',
                'image' => 'images/rooms/deluxe-suite.jpg',
                'facilities' => ['High-Speed Wi-Fi', 'Air Conditioning', 'Mini Refrigerator', 'Flat Screen TV', 'Work Desk', 'In-Room Safe'],
            ],
            [
                'room_type' => 'Executive Family Suite',
                'room_number' => '305',
                'floor' => 3,
                'price_per_night' => 135.00,
                'status' => 'available',
                'description' => 'A generous two-bed suite designed for families, with a shared lounge area and plenty of storage.',
                'image' => 'images/rooms/executive-family-suite.jpg',
                'facilities' => ['High-Speed Wi-Fi', 'Air Conditioning', 'Mini Refrigerator', 'Flat Screen TV', 'Work Desk', 'In-Room Safe'],
            ],
        ];

        foreach ($rooms as $roomData) {
            $facilityNames = $roomData['facilities'];
            $roomType = $roomTypeModels[$roomData['room_type']];

            unset($roomData['facilities'], $roomData['room_type']);

            $room = Room::firstOrCreate(
                ['room_number' => $roomData['room_number']],
                ['room_type_id' => $roomType->id, ...$roomData]
            );

            $room->facilities()->syncWithoutDetaching(
                array_map(
                    fn (string $name): int => $facilityModels[$name]->id,
                    $facilityNames
                )
            );
        }
    }
}
