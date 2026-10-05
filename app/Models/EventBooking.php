<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\EventBookingFactory;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class EventBooking extends Model
{
    /** @use HasFactory<EventBookingFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
    ];

    public const ACTIVE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
    ];

    protected $fillable = [
        'user_id',
        'venue_id',
        'event_type',
        'starts_at',
        'ends_at',
        'guest_count',
        'special_requests',
        'quoted_price',
        'status',
        'status_note',
        'processed_by',
        'processed_at',
        'cancelled_at',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'guest_count' => 'integer',
            'quoted_price' => 'decimal:2',
            'processed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function scopeOverlapping(Builder $query, CarbonInterface $startsAt, CarbonInterface $endsAt): Builder
    {
        return $query
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt);
    }

    /**
     * @param  array{event_type: string, starts_at: CarbonInterface, ends_at: CarbonInterface, guest_count: int, special_requests: ?string}  $attributes
     */
    public static function reserve(User $customer, Venue $venue, array $attributes): self
    {
        return DB::transaction(function () use ($customer, $venue, $attributes): self {
            $lockedVenue = Venue::query()->lockForUpdate()->findOrFail($venue->getKey());

            if (! $lockedVenue->is_active) {
                throw new DomainException('This venue is not currently available for reservations.');
            }

            if (! $lockedVenue->supportsEventType($attributes['event_type'])) {
                throw new DomainException('This venue does not support the selected event type.');
            }

            if ($attributes['guest_count'] > $lockedVenue->capacity) {
                throw new DomainException("The guest count exceeds this venue's capacity of {$lockedVenue->capacity}.");
            }

            $hasConflict = self::query()
                ->whereBelongsTo($lockedVenue)
                ->active()
                ->overlapping($attributes['starts_at'], $attributes['ends_at'])
                ->exists();

            if ($hasConflict) {
                throw new DomainException('This venue already has an active reservation during the selected time.');
            }

            return self::query()->create([
                'user_id' => $customer->getKey(),
                'venue_id' => $lockedVenue->getKey(),
                'event_type' => $attributes['event_type'],
                'starts_at' => $attributes['starts_at'],
                'ends_at' => $attributes['ends_at'],
                'guest_count' => $attributes['guest_count'],
                'special_requests' => $attributes['special_requests'],
                'quoted_price' => $lockedVenue->price,
                'status' => self::STATUS_PENDING,
            ]);
        }, 5);
    }

    public function approve(User $staff, ?string $note = null): void
    {
        DB::transaction(function () use ($staff, $note): void {
            $venue = Venue::query()->lockForUpdate()->findOrFail($this->venue_id);
            $booking = self::query()->lockForUpdate()->findOrFail($this->getKey());

            if ($booking->status !== self::STATUS_PENDING) {
                throw new DomainException('Only pending reservations can be approved.');
            }

            if (! $venue->is_active) {
                throw new DomainException('This reservation cannot be approved while its venue is archived.');
            }

            if (! $venue->supportsEventType($booking->event_type)) {
                throw new DomainException('This venue no longer supports the reservation event type.');
            }

            if ($booking->guest_count > $venue->capacity) {
                throw new DomainException("The guest count now exceeds this venue's capacity of {$venue->capacity}.");
            }

            $hasConflict = self::query()
                ->where('venue_id', $booking->venue_id)
                ->whereKeyNot($booking->getKey())
                ->active()
                ->overlapping($booking->starts_at, $booking->ends_at)
                ->exists();

            if ($hasConflict) {
                throw new DomainException('This reservation now conflicts with another active reservation.');
            }

            $booking->update([
                'status' => self::STATUS_APPROVED,
                'status_note' => $note,
                'processed_by' => $staff->getKey(),
                'processed_at' => now(),
            ]);
        }, 5);

        $this->refresh();
    }

    public function reject(User $staff, string $reason): void
    {
        DB::transaction(function () use ($staff, $reason): void {
            Venue::query()->lockForUpdate()->findOrFail($this->venue_id);
            $booking = self::query()->lockForUpdate()->findOrFail($this->getKey());

            if ($booking->status !== self::STATUS_PENDING) {
                throw new DomainException('Only pending reservations can be rejected.');
            }

            $booking->update([
                'status' => self::STATUS_REJECTED,
                'status_note' => $reason,
                'processed_by' => $staff->getKey(),
                'processed_at' => now(),
            ]);
        }, 5);

        $this->refresh();
    }

    public function cancel(User $cancelledBy, ?string $reason = null): void
    {
        DB::transaction(function () use ($cancelledBy, $reason): void {
            Venue::query()->lockForUpdate()->findOrFail($this->venue_id);
            $booking = self::query()->lockForUpdate()->findOrFail($this->getKey());

            if (! in_array($booking->status, self::ACTIVE_STATUSES, true)) {
                throw new DomainException('Only pending or approved reservations can be cancelled.');
            }

            if (! $booking->starts_at->isFuture()) {
                throw new DomainException('A reservation cannot be cancelled after its start time.');
            }

            $booking->update([
                'status' => self::STATUS_CANCELLED,
                'status_note' => $reason,
                'processed_by' => $cancelledBy->getKey(),
                'processed_at' => now(),
                'cancelled_at' => now(),
            ]);
        }, 5);

        $this->refresh();
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true)
            && $this->starts_at->isFuture();
    }
}
