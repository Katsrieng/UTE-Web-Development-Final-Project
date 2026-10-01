<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use Illuminate\Http\Request;

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
            'name'        => 'required|string|max:255|unique:room_types,name',
            'description' => 'nullable|string',
            'base_price'  => 'required|numeric|min:0',
            'capacity'    => 'required|integer|min:1',
            'bed_type'    => 'nullable|string|max:100',
        ]);

        RoomType::create($validated);

        return redirect()->route('room-types.index')->with('success', 'Room Type created successfully.');
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
            'name'        => 'required|string|max:255|unique:room_types,name,' . $roomType->id,
            'description' => 'nullable|string',
            'base_price'  => 'required|numeric|min:0',
            'capacity'    => 'required|integer|min:1',
            'bed_type'    => 'nullable|string|max:100',
        ]);

        $roomType->update($validated);

        return redirect()->route('room-types.index')->with('success', 'Room Type updated successfully.');
    }

    /**
     * Remove the specified room type from storage.
     */
    public function destroy(RoomType $roomType)
    {
        $roomType->delete();

        return redirect()->route('room-types.index')->with('success', 'Room Type deleted successfully.');
    }
}