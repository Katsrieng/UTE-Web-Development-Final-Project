<?php

namespace App\Http\Controllers;

use App\Models\RoomImage;
use App\Models\RoomType;
use App\Services\RoomGalleryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RoomTypeController extends Controller
{
    /**
     * Display a listing of room types.
     */
    public function index()
    {
        $roomTypes = RoomType::withCount('rooms')->latest()->paginate(10);

        return view('room_types.index', compact('roomTypes'));
    }

    /**
     * Show the form for creating a new room type.
     */
    public function create()
    {
        return view('room_types.create');
    }

    /**
     * Store a newly created room type in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:room_types,name',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'capacity' => 'required|integer|min:1',
            'bed_type' => 'nullable|string|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('room_types', 'public');
            $validated['image'] = '/storage/'.$path;
        }

        RoomType::create($validated);

        return redirect()->route('management.room-types.index')->with('success', 'Room Type created successfully.');
    }

    /**
     * Display the specified room type.
     */
    public function show(RoomType $roomType)
    {
        $roomType->load('rooms');

        return view('room_types.show', compact('roomType'));
    }

    /**
     * Show the form for editing the specified room type.
     */
    public function edit(RoomType $roomType)
    {
        return view('room_types.edit', compact('roomType'));
    }

    /**
     * Update the specified room type in storage.
     */
    public function update(Request $request, RoomType $roomType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:room_types,name,'.$roomType->id,
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'capacity' => 'required|integer|min:1',
            'bed_type' => 'nullable|string|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            // Delete the previous uploaded file if one exists
            if ($roomType->image && str_starts_with($roomType->image, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $roomType->image));
            }

            $path = $request->file('image')->store('room_types', 'public');
            $validated['image'] = '/storage/'.$path;
        }

        $roomType->update($validated);

        return redirect()->route('management.room-types.index')->with('success', 'Room Type updated successfully.');
    }

    /**
     * Remove the specified room type from storage.
     */
    public function destroy(RoomType $roomType, RoomGalleryService $gallery)
    {
        if ($roomType->image && str_starts_with($roomType->image, '/storage/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $roomType->image));
        }

        DB::transaction(function () use ($roomType, $gallery) {
            $locked = RoomType::whereKey($roomType->id)->lockForUpdate()->firstOrFail();
            $rooms = $locked->rooms()->orderBy('id')->lockForUpdate()->get();
            $paths = RoomImage::whereIn('room_id', $rooms->pluck('id'))->pluck('image_path')->all();
            $locked->delete();
            DB::afterCommit(fn () => $gallery->cleanup($paths));
        });

        return redirect()->route('management.room-types.index')->with('success', 'Room Type deleted successfully.');
    }
}
