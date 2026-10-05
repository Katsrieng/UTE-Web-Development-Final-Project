<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Models\BookingStatusLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
            'user_id' => [
                'required', 'integer',
                Rule::exists('users', 'id')->where('role', User::ROLE_CUSTOMER)->where('is_active', true),
            ],
            'room_id' => 'required|exists:rooms,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'number_of_guests' => 'required|integer|min:1',
            'special_request' => 'nullable|string',
        ], [
            'user_id.exists' => 'Please select an active customer for this booking.',
        ]);

        $room = $this->validateRoom($validated);

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
        $oldStatus = $booking->status;

        $validated = $request->validate([
            'user_id' => [
                'required', 'integer',
                Rule::exists('users', 'id')->where('role', User::ROLE_CUSTOMER)->where('is_active', true),
            ],
            'room_id' => 'required|exists:rooms,id',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
            'number_of_guests' => 'required|integer|min:1',
            'status' => 'required|in:Pending,Confirmed,Checked In,Checked Out,Cancelled',
            'special_request' => 'nullable|string',
        ], [
            'user_id.exists' => 'Please select an active customer for this booking.',
        ]);

        $room = $this->validateRoom($validated, $booking);

        $checkIn = \Carbon\Carbon::parse($validated['check_in_date']);
        $checkOut = \Carbon\Carbon::parse($validated['check_out_date']);

        $numberOfNights = $checkIn->diffInDays($checkOut);

        $validated['total_amount'] = $room->price_per_night * $numberOfNights;

        $booking->update($validated);
        if ($oldStatus !== $booking->status) {
            BookingStatusLog::create([
                'booking_id' => $booking->id,
                'changed_by' => $request->user()->id,
                'old_status' => $oldStatus,
                'new_status' => $booking->status,
                'note' => null,
            ]);
        }

        return redirect()
            ->route('bookings.index')
            ->with('success', 'Booking updated successfully.');
    }
    /**
     * Validate the selected room before pricing or saving the booking.
     */
    private function validateRoom(array $validated, ?Booking $booking = null): Room
    {
        $room = Room::with('roomType')->findOrFail($validated['room_id']);
        $errors = [];

        // An ordinary update may keep its existing room even if its operational status changed.
        if ((! $booking || (int) $booking->room_id !== (int) $room->id) && $room->status !== 'available') {
            $errors['room_id'] = 'Please select a room with an available operational status.';
        }

        $capacity = $room->roomType->capacity;
        if ($validated['number_of_guests'] > $capacity) {
            $errors['number_of_guests'] = "The number of guests may not exceed this room type's capacity of {$capacity}.";
        }

        // Terminal bookings do not reserve dates; reactivating one must check availability again.
        if (in_array($validated['status'] ?? 'Pending', Booking::ACTIVE_STATUSES, true)) {
            $overlaps = Booking::overlapping(
                $room->id,
                \Carbon\Carbon::parse($validated['check_in_date'])->toDateString(),
                \Carbon\Carbon::parse($validated['check_out_date'])->toDateString(),
                $booking?->id
            )->exists();

            if ($overlaps && ! isset($errors['room_id'])) {
                $errors['room_id'] = 'This room already has an active booking during the selected dates.';
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return $room;
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
