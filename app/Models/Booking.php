<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    public const ACTIVE_STATUSES = ['Pending', 'Confirmed', 'Checked In'];

    public const STATUS_TRANSITIONS = [
        'Pending' => ['Confirmed', 'Cancelled'],
        'Confirmed' => ['Checked In', 'Cancelled'],
        'Checked In' => ['Checked Out'],
        'Checked Out' => [],
        'Cancelled' => [],
    ];

    protected $fillable = [
    'user_id',
    'room_id',
    'check_in_date',
    'check_out_date',
    'number_of_guests',
    'total_amount',
    'status',
    'special_request',
];
    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::STATUS_TRANSITIONS[$this->status] ?? [], true);
    }

    /**
     * Checkout is exclusive, so back-to-back stays do not overlap.
     */
    public function scopeOverlapping(
        Builder $query,
        int $roomId,
        string $checkIn,
        string $checkOut,
        ?int $exceptBookingId = null
    ): Builder {
        $query->where('room_id', $roomId)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->where('check_in_date', '<', $checkOut)
            ->where('check_out_date', '>', $checkIn);

        if ($exceptBookingId !== null) {
            $query->where('id', '!=', $exceptBookingId);
        }

        return $query;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(BookingStatusLog::class);
    }
}
