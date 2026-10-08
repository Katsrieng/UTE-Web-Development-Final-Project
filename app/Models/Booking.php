<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    public function payments(): HasMany { return $this->hasMany(Payment::class); }

    public function bookingPackages(): HasMany
    {
        return $this->hasMany(BookingPackage::class);
    }

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'booking_packages')->withPivot('quantity', 'price')->withTimestamps();
    }

    public function packageTotalCents(): int
    {
        return $this->bookingPackages->sum(fn ($line) => (int) round((float) $line->price * 100) * $line->quantity);
    }

    protected function casts(): array
    {
        return ['membership_discount_percentage' => 'decimal:2', 'membership_discount_amount' => 'decimal:2'];
    }

    public function subtotalCents(): int
    {
        return (int) round((float) $this->total_amount * 100)
            + (int) round((float) ($this->membership_discount_amount ?? 0) * 100);
    }

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
        'membership_id',
        'membership_name',
        'membership_discount_percentage',
        'membership_discount_amount',
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
