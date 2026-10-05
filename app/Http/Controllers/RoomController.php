<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomType;
use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::with(['roomType', 'facilities'])->latest()->paginate(10);
        return view('rooms.index', compact('rooms'));
    }

    public function create()
    {
        $roomTypes = RoomType::all();
        $facilities = Facility::all();
        return view('rooms.create', compact('roomTypes', 'facilities'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_type_id'    => 'required|exists:room_types,id',
            'room_number'     => 'required|string|max:50|unique:rooms,room_number',
            'floor'           => 'required|integer|min:1',
            'price_per_night' => 'required|numeric|min:0',
            'status'          => 'required|in:available,booked,occupied,maintenance,cleaning',
            'description'     => 'nullable|string',
            'image'           => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'facilities'      => 'nullable|array',
            'facilities.*'    => 'exists:facilities,id',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('rooms', 'public');
        }

        $room = Room::create($validated);

        if ($request->has('facilities')) {
            $room->facilities()->sync($request->facilities);
        }

        return redirect()->route('management.rooms.index')->with('success', 'Room created successfully.');
    }

    public function show(Room $room)
    {
        $room->load(['roomType', 'facilities']);
        return view('rooms.show', compact('room'));
    }

    public function edit(Room $room)
    {
        $roomTypes = RoomType::all();
        $facilities = Facility::all();
        $room->load('facilities');
        return view('rooms.edit', compact('room', 'roomTypes', 'facilities'));
    }

    public function update(Request $request, Room $room)
    {
        $validated = $request->validate([
            'room_type_id'    => 'required|exists:room_types,id',
            'room_number'     => 'required|string|max:50|unique:rooms,room_number,' . $room->id,
            'floor'           => 'required|integer|min:1',
            'price_per_night' => 'required|numeric|min:0',
            'status'          => 'required|in:available,booked,occupied,maintenance,cleaning',
            'description'     => 'nullable|string',
            'image'           => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'facilities'      => 'nullable|array',
            'facilities.*'    => 'exists:facilities,id',
        ]);

        if ($request->hasFile('image')) {
            if ($room->image && Storage::disk('public')->exists($room->image)) {
                Storage::disk('public')->delete($room->image);
            }
            $validated['image'] = $request->file('image')->store('rooms', 'public');
        }

        // Force fill and persist directly
        $room->fill($validated);
        $room->save();

        if ($request->has('facilities')) {
            $room->facilities()->sync($request->facilities);
        } else {
            $room->facilities()->detach();
        }

        return redirect()->route('management.rooms.index')->with('success', 'Room #' . $room->room_number . ' updated successfully!');
    }

    public function destroy(Room $room)
    {
        if ($room->image && Storage::disk('public')->exists($room->image)) {
            Storage::disk('public')->delete($room->image);
        }

        $room->delete();

        return redirect()->route('management.rooms.index')->with('success', 'Room deleted successfully.');
    }
}
