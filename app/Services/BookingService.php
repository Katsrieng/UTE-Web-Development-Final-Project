<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingStatusLog;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function create(array $validated): Booking
    {
        $room = $this->validateRoom($validated);

        return Booking::create(array_merge($validated, [
            'total_amount' => $this->calculateTotal($room, $validated['check_in_date'], $validated['check_out_date']),
            'status' => 'Pending',
        ]));
    }

    public function calculateTotal(Room $room, string $checkIn, string $checkOut): float
    {
        $numberOfNights = Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));

        return $room->price_per_night * $numberOfNights;
    }

    /** Validate room eligibility, capacity, and the existing checkout-exclusive overlap rules. */
    public function validateRoom(array $validated, ?Booking $booking = null): Room
    {
        $room = Room::with('roomType')->findOrFail($validated['room_id']);
        $errors = [];

        if ((! $booking || (int) $booking->room_id !== (int) $room->id) && $room->status !== 'available') {
            $errors['room_id'] = 'Please select a room with an available operational status.';
        }

        $capacity = $room->roomType->capacity;
        if ($validated['number_of_guests'] > $capacity) {
            $errors['number_of_guests'] = "The number of guests may not exceed this room type's capacity of {$capacity}.";
        }

        if (in_array($validated['status'] ?? 'Pending', Booking::ACTIVE_STATUSES, true)) {
            $overlaps = Booking::overlapping(
                $room->id,
                Carbon::parse($validated['check_in_date'])->toDateString(),
                Carbon::parse($validated['check_out_date'])->toDateString(),
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

    public function transition(string $id, string $newStatus, User $actor): Booking
    {
        return DB::transaction(function () use ($id, $newStatus, $actor) {
            $query = Booking::query()->lockForUpdate();
            if ($actor->isCustomer()) {
                abort_unless($actor->is_active && $newStatus === 'Cancelled', 403);
                $query->where('user_id', $actor->id);
            } else {
                abort_unless($actor->hasRole(User::ROLE_ADMIN, User::ROLE_STAFF), 403);
            }

            $booking = $query->findOrFail($id);
            $room = Room::query()->lockForUpdate()->findOrFail($booking->room_id);

            if (! $booking->canTransitionTo($newStatus)) {
                throw ValidationException::withMessages([
                    'status' => "Cannot change booking status from {$booking->status} to {$newStatus}.",
                ]);
            }

            if ($newStatus === 'Checked In' && $room->status !== 'available') {
                throw ValidationException::withMessages([
                    'room_id' => 'Check-in requires a room with an available operational status.',
                ]);
            }

            $oldStatus = $booking->status;
            $booking->update(['status' => $newStatus]);
            if ($newStatus === 'Checked In') {
                $room->update(['status' => 'occupied']);
            } elseif ($newStatus === 'Checked Out') {
                $room->update(['status' => 'cleaning']);
            }

            BookingStatusLog::create([
                'booking_id' => $booking->id,
                'changed_by' => $actor->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'note' => null,
            ]);

            return $booking;
        });
    }
}
