<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RoomType;
use App\Models\Facility;

class RoomModuleSeeder extends Seeder
{
    public function run(): void
    {
        $facilities = [
            ['name' => 'High-Speed Wi-Fi', 'description' => 'Complimentary wireless internet', 'status' => 1],
            ['name' => 'Air Conditioning', 'description' => 'Climate-controlled cooling', 'status' => 1],
            ['name' => 'Mini Refrigerator', 'description' => 'In-room drinks and snack fridge', 'status' => 1],
            ['name' => 'Flat Screen TV', 'description' => 'HD cable channels included', 'status' => 1],
        ];

        foreach ($facilities as $facility) {
            Facility::firstOrCreate(['name' => $facility['name']], $facility);
        }

        $roomTypes = [
            ['name' => 'Standard Single', 'base_price' => 35.00, 'capacity' => 1, 'bed_type' => '1 Single Bed'],
            ['name' => 'Deluxe Suite', 'base_price' => 75.00, 'capacity' => 2, 'bed_type' => '1 King Bed'],
            ['name' => 'Executive Family Suite', 'base_price' => 120.00, 'capacity' => 4, 'bed_type' => '2 Queen Beds'],
        ];

        foreach ($roomTypes as $type) {
            RoomType::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}