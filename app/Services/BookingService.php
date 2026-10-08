<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingStatusLog;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\Package;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function create(array $validated): Booking
    {
        return DB::transaction(function () use ($validated) {
            Room::whereKey($validated['room_id'])->lockForUpdate()->firstOrFail();
            $room = $this->validateRoom($validated);
            $quote = $this->quotePackages($validated['packages'] ?? [], (int) $validated['number_of_guests'], true);
            $subtotal = $this->combineTotal($this->calculateTotal($room, $validated['check_in_date'], $validated['check_out_date']), $quote['total_cents']);
            $membershipPricing = $this->quoteMembershipDiscount((int) $validated['user_id'], $subtotal, true);
            unset($validated['packages']);
            $booking = Booking::create(array_merge($validated, $membershipPricing, [
                'status' => 'Pending',
            ]));
            foreach ($quote['lines'] as $line) {
                $booking->bookingPackages()->create(['package_id' => $line['package_id'], 'quantity' => $line['quantity'], 'price' => $line['price']]);
            }

            return $booking;
        });
    }

    public function quoteMembershipDiscount(int $customerId, float $subtotal, bool $lock = false): array
    {
        $query = Membership::where('user_id', $customerId)->discountEligible()->orderByDesc('start_date')->orderByDesc('id');
        if ($lock) {
            $query->lockForUpdate();
        }
        $membership = $query->first();
        $type = null;
        if ($membership) {
            $types = MembershipType::whereKey($membership->membership_type_id);
            if ($lock) {
                $types->lockForUpdate();
            }
            $type = $types->first();
            $membership->setRelation('membershipType', $type);
            if (! $membership->isActive() || ! $type || $type->status !== 'active'
                || (float) $type->discount_percentage < 0 || (float) $type->discount_percentage > 100) {
                $membership = null;
            }
        }

        return array_merge($this->applyMembershipDiscount($subtotal, $membership ? $type->discount_percentage : '0.00'), [
            'membership_id' => $membership?->id,
            'membership_name' => $membership ? $type->name : null,
            'membership_discount_percentage' => $membership ? $type->discount_percentage : '0.00',
        ]);
    }

    public function applyMembershipDiscount(float $subtotal, string $percentage): array
    {
        // Round once, half up, using integer cents and hundredths of a percentage point.
        $subtotalCents = (int) round($subtotal * 100);
        $basisPoints = (int) round((float) $percentage * 100);
        if ($basisPoints < 0 || $basisPoints > 10000) {
            throw ValidationException::withMessages(['membership_discount_percentage' => 'The saved membership discount is invalid.']);
        }
        $discountCents = intdiv($subtotalCents * $basisPoints + 5000, 10000);

        return ['membership_discount_amount' => $discountCents / 100, 'total_amount' => ($subtotalCents - $discountCents) / 100];
    }

    public function quotePackages(array $selection, int $guests, bool $lock = false): array
    {
        $query = Package::active()->whereIn('id', array_column($selection, 'package_id'))->orderBy('id');
        if ($lock) {
            $query->lockForUpdate();
        }
        $packages = $query->get()->keyBy('id');
        $lines = [];
        $total = 0;
        foreach ($selection as $index => $selected) {
            $package = $packages->get($selected['package_id']);
            if (! $package) {
                throw ValidationException::withMessages(["packages.$index.package_id" => 'This add-on is no longer available. Please review your selection.']);
            }
            $submittedQuantity = $selected['quantity'] ?? 1;
            $limit = $package->bookingQuantityLimit($guests);
            if (filter_var($submittedQuantity, FILTER_VALIDATE_INT) === false || $submittedQuantity < 1 || $submittedQuantity > $limit) {
                throw ValidationException::withMessages(["packages.$index.quantity" => "Choose between 1 and {$limit} units for this add-on."]);
            }
            $quantity = (int) $submittedQuantity;
            $lineCents = (int) round((float) $package->price * 100) * $quantity;
            $total += $lineCents;
            $lines[] = ['package_id' => $package->id, 'name' => $package->name, 'quantity' => $quantity, 'price' => $package->price, 'line_total' => $lineCents / 100];
        }

        return ['lines' => $lines, 'total_cents' => $total];
    }

    public function combineTotal(float $roomTotal, int $packageCents): float
    {
        $total = (int) round($roomTotal * 100) + $packageCents;
        if ($total > 9999999999) {
            throw ValidationException::withMessages(['total_amount' => 'The booking total exceeds the supported amount.']);
        }

        return $total / 100;
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
                abort_unless($actor->hasRole(User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_STAFF), 403);
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

    /** Internal payment side effect; the caller must hold the booking lock in its payment transaction. */
    public function confirmPaidBooking(Booking $booking, User $actor): void
    {
        if ($booking->status !== 'Pending') {
            return;
        }
        if (! $booking->canTransitionTo('Confirmed')) {
            return;
        }
        $booking->update(['status' => 'Confirmed']);
        BookingStatusLog::create(['booking_id' => $booking->id, 'changed_by' => $actor->id,
            'old_status' => 'Pending', 'new_status' => 'Confirmed', 'note' => 'Payment recorded as Paid.']);
    }
}
