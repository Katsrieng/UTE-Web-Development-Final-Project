<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\EventBooking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/** Read-only filters shared by the staff and ownership-scoped customer lists. */
class ListFilters
{
    public static function term(Request $request): string
    {
        $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        return trim((string) $request->query('search', ''));
    }

    private static function referenceId(string $term): ?int
    {
        return preg_match('/^(?:(?:booking|reservation)\s*)?#?(\d+)$/i', $term, $matches)
            ? (int) $matches[1] : null;
    }

    public static function bookings(Builder $query, Request $request, bool $staff = false): Builder
    {
        $term = self::term($request);
        if ($term !== '') {
            $query->where(function (Builder $search) use ($term, $staff): void {
                $search->whereHas('room', fn (Builder $rooms) => $rooms->where('room_number', 'like', "%{$term}%")
                    ->orWhereHas('roomType', fn (Builder $types) => $types->where('name', 'like', "%{$term}%")));
                if ($id = self::referenceId($term)) { $search->orWhere('id', $id); }
                if ($staff) {
                    $search->orWhereHas('user', fn (Builder $users) => $users->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
                }
            });
        }
        if (in_array($request->query('status'), array_keys(Booking::STATUS_TRANSITIONS), true)) {
            $query->where('status', $request->query('status'));
        }
        if (in_array($request->query('payment_status'), ['Pending', 'Paid', 'Refunded'], true)) {
            $query->whereHas('payments', fn (Builder $payments) => $payments->where('status', $request->query('payment_status')));
        }
        return $query;
    }

    public static function events(Builder $query, Request $request, bool $staff = false): Builder
    {
        $term = self::term($request);
        if ($term !== '') {
            $query->where(function (Builder $search) use ($term, $staff): void {
                $search->whereHas('venue', fn (Builder $venues) => $venues->where('name', 'like', "%{$term}%"));
                if ($id = self::referenceId($term)) { $search->orWhere('id', $id); }
                if ($staff) {
                    $search->orWhere('event_type', 'like', "%{$term}%")
                        ->orWhereHas('user', fn (Builder $users) => $users->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
                }
            });
        }
        if (in_array($request->query('status'), EventBooking::STATUSES, true)) {
            $query->where('status', $request->query('status'));
        }
        if ($venueId = filter_var($request->query('venue_id'), FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]])) {
            $query->where('venue_id', $venueId);
        }
        return $query;
    }

    public static function payments(Builder $query, Request $request): Builder
    {
        $term = self::term($request);
        if ($term !== '') {
            $query->where(function (Builder $search) use ($term): void {
                $search->where('reference_number', 'like', "%{$term}%")
                    ->orWhereHas('user', fn (Builder $users) => $users->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
                if ($id = self::referenceId($term)) { $search->orWhere('booking_id', $id); }
            });
        }
        if (in_array($request->query('status'), ['Pending', 'Paid', 'Refunded'], true)) {
            $query->where('status', $request->query('status'));
        }
        if (in_array($request->query('payment_method'), ['Card', 'ABA / KHQR', 'Cash at Hotel'], true)) {
            $query->where('payment_method', $request->query('payment_method'));
        }
        return $query;
    }
}
