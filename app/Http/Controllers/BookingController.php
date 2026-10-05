<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $bookings = Booking::with(['user', 'room'])
            ->latest()
            ->paginate(10);

        return view('bookings.index', compact('bookings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::where('is_active', true)
            ->where('role', 'customer')
            ->orderBy('name')
            ->get();

        $rooms = Room::where('status', 'available')
            ->orderBy('room_number')
            ->get();

        return view('bookings.create', compact('users', 'rooms'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:rooms,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'number_of_guests' => 'required|integer|min:1',
            'special_request' => 'nullable|string',
        ]);

        $room = Room::findOrFail($validated['room_id']);

        $checkIn = \Carbon\Carbon::parse($validated['check_in_date']);
        $checkOut = \Carbon\Carbon::parse($validated['check_out_date']);

        $numberOfNights = $checkIn->diffInDays($checkOut);

        $validated['total_amount'] = $room->price_per_night * $numberOfNights;
        $validated['status'] = 'Pending';

        Booking::create($validated);

        return redirect()
            ->route('bookings.index')
            ->with('success', 'Booking created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $booking = Booking::with([
            'user',
            'room',
            'statusLogs.changedBy'
        ])->findOrFail($id);

        return view('bookings.show', compact('booking'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $booking = Booking::findOrFail($id);

        $users = User::where('is_active', true)
            ->where('role', 'customer')
            ->orderBy('name')
            ->get();

        $rooms = Room::orderBy('room_number')->get();

        return view('bookings.edit', compact('booking', 'users', 'rooms'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $booking = Booking::findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:rooms,id',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
            'number_of_guests' => 'required|integer|min:1',
            'status' => 'required|in:Pending,Confirmed,Checked In,Checked Out,Cancelled',
            'special_request' => 'nullable|string',
        ]);

        $room = Room::findOrFail($validated['room_id']);

        $checkIn = \Carbon\Carbon::parse($validated['check_in_date']);
        $checkOut = \Carbon\Carbon::parse($validated['check_out_date']);

        $numberOfNights = $checkIn->diffInDays($checkOut);

        $validated['total_amount'] = $room->price_per_night * $numberOfNights;

        $booking->update($validated);

        return redirect()
            ->route('bookings.index')
            ->with('success', 'Booking updated successfully.');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $booking = Booking::findOrFail($id);

        $booking->delete();

        return redirect()
            ->route('bookings.index')
            ->with('success', 'Booking deleted successfully.');
    }
}
