<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Services\RoomGalleryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomImageController extends Controller
{
    public function store(Request $request, Room $room, RoomGalleryService $gallery)
    {
        $request->validate(['images' => 'required|array|min:1|max:5', 'images.*' => 'required|image|mimes:jpeg,jpg,png,webp|max:2048']);
        $gallery->save(fn () => $room, $request->file('images'));

        return back()->with('success', 'Room photos added.');
    }

    public function update(Request $request, Room $room, int $roomImage)
    {
        $image = $room->images()->findOrFail($roomImage);
        $validated = $request->validate(['alt_text' => 'nullable|string|max:191', 'sort_order' => 'required|integer|min:0|max:9999']);
        DB::transaction(function () use ($room, $image, $validated) {
            Room::whereKey($room->id)->lockForUpdate()->firstOrFail();
            $room->images()->findOrFail($image->id)->update($validated);
        });

        return back()->with('success', 'Photo details updated.');
    }

    public function primary(Room $room, int $roomImage, RoomGalleryService $gallery)
    {
        $gallery->primary($room, $roomImage);

        return back()->with('success', 'Cover photo updated.');
    }

    public function destroy(Room $room, int $roomImage, RoomGalleryService $gallery)
    {
        $gallery->deleteImage($room, $roomImage);

        return back()->with('success', 'Photo deleted.');
    }
}
