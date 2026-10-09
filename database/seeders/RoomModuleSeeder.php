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
            'High-Speed Wi-Fi' => 'Complimentary Wi-Fi throughout the resort.',
            'Swimming Pool' => 'Outdoor pool with a relaxed coastal setting.',
            'Breakfast' => 'Breakfast service available at the resort restaurant.',
            'Air Conditioning' => 'Individually controlled room cooling.',
            'Parking' => 'Convenient on-site guest parking.',
            'Beach Access' => 'Easy access to the resort beach.',
        ];
        foreach ($facilities as $name => $description) {
            Facility::firstOrCreate(['name' => $name], ['description' => $description, 'status' => 'open']);
        }

        $types = [
            ['name' => 'Deluxe Ocean Room', 'description' => 'A comfortable king room with a coastal outlook and room to unwind.', 'base_price' => 120, 'capacity' => 2, 'bed_type' => '1 King Bed'],
            ['name' => 'Garden Room', 'description' => 'A restful room near the gardens for a relaxed Utopia Bay stay.', 'base_price' => 70, 'capacity' => 2, 'bed_type' => '1 Queen Bed'],
            ['name' => 'Family Suite', 'description' => 'A spacious suite with room for the whole family.', 'base_price' => 165, 'capacity' => 4, 'bed_type' => '2 Queen Beds'],
            ['name' => 'Ocean Villa', 'description' => 'A private villa with generous living space and a coastal view.', 'base_price' => 245, 'capacity' => 4, 'bed_type' => '2 King Beds'],
        ];
        foreach ($types as $type) {
            RoomType::firstOrCreate(['name' => $type['name']], $type);
        }

        $rooms = [
            ['room_number' => 'UB-101', 'type' => 'Deluxe Ocean Room', 'floor' => 1, 'rate' => 120, 'image' => 'images/rooms/deluxe-suite.jpg', 'facilities' => ['High-Speed Wi-Fi', 'Breakfast', 'Air Conditioning', 'Beach Access']],
            ['room_number' => 'UB-102', 'type' => 'Garden Room', 'floor' => 1, 'rate' => 70, 'image' => 'images/rooms/standard-single.jpg', 'facilities' => ['High-Speed Wi-Fi', 'Air Conditioning', 'Parking']],
            ['room_number' => 'UB-201', 'type' => 'Family Suite', 'floor' => 2, 'rate' => 165, 'image' => 'images/rooms/executive-family-suite.jpg', 'facilities' => ['High-Speed Wi-Fi', 'Swimming Pool', 'Air Conditioning', 'Beach Access']],
            ['room_number' => 'UB-202', 'type' => 'Garden Room', 'floor' => 2, 'rate' => 70, 'image' => 'images/rooms/standard-single.jpg', 'facilities' => ['High-Speed Wi-Fi', 'Air Conditioning', 'Parking']],
            ['room_number' => 'UB-301', 'type' => 'Ocean Villa', 'floor' => 3, 'rate' => 245, 'image' => 'images/rooms/deluxe-suite.jpg', 'facilities' => ['High-Speed Wi-Fi', 'Swimming Pool', 'Breakfast', 'Air Conditioning', 'Beach Access', 'Parking']],
            ['room_number' => 'UB-302', 'type' => 'Deluxe Ocean Room', 'floor' => 3, 'rate' => 120, 'image' => 'images/rooms/deluxe-suite.jpg', 'facilities' => ['High-Speed Wi-Fi', 'Air Conditioning', 'Beach Access']],
        ];
        foreach ($rooms as $data) {
            $type = RoomType::where('name', $data['type'])->firstOrFail();
            $room = Room::firstOrCreate(['room_number' => $data['room_number']], [
                'room_type_id' => $type->id,
                'floor' => $data['floor'],
                'price_per_night' => $data['rate'],
                'status' => 'available',
                'description' => $type->description,
                'image' => $data['image'],
            ]);
            $facilityIds = Facility::whereIn('name', $data['facilities'])->pluck('id')->all();
            $room->facilities()->syncWithoutDetaching($facilityIds);
        }
    }
}
