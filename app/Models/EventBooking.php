<?php

namespace App\Models;

use App\Notifications\EventReservationStatusUpdatedNotification;
use Carbon\CarbonInterface;
use Database\Factories\EventBookingFactory;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
            $notification = EventReservationStatusUpdatedNotification::forReservation($booking);
            DB::afterCommit(fn () => User::find($booking->user_id)?->notify($notification));
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
            $notification = EventReservationStatusUpdatedNotification::forReservation($booking);
            DB::afterCommit(fn () => User::find($booking->user_id)?->notify($notification));
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
            if (! $cancelledBy->isCustomer()) {
                $notification = EventReservationStatusUpdatedNotification::forReservation($booking);
                DB::afterCommit(fn () => User::find($booking->user_id)?->notify($notification));
            }
        }, 5);

        $this->refresh();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'event_booking_id');
    }

    private function hasProcessingHistory(): bool
    {
        return $this->processed_at !== null || $this->processed_by !== null
            || $this->cancelled_at !== null || filled($this->status_note);
    }

    private function hasLinkedPayments(): bool
    {
        return array_key_exists('payments_exists', $this->getAttributes())
            ? (bool) $this->payments_exists
            : $this->payments()->exists();
    }

    public function staffEditBlockReason(): ?string
    {
        if ($this->hasLinkedPayments()) {
            return 'This reservation has linked payments. Its details must be retained unchanged.';
        }
        if ($this->status !== self::STATUS_PENDING) {
            return 'Only pending reservations can be edited. Rejected reservations cannot be reopened in the current workflow.';
        }
        if ($this->hasProcessingHistory()) {
            return 'This reservation has processing history and cannot be edited.';
        }
        if (! $this->starts_at->isFuture()) {
            return 'Reservations that have already started cannot be edited.';
        }
        return null;
    }

    public function staffDeleteBlockReason(): ?string
    {
        if ($this->hasLinkedPayments()) {
            return 'This reservation has linked payments and cannot be deleted. Payment history must be preserved.';
        }
        if (! in_array($this->status, [self::STATUS_PENDING, self::STATUS_REJECTED, self::STATUS_CANCELLED], true)) {
            return 'Approved reservations cannot be deleted.';
        }
        if ($this->hasProcessingHistory()) {
            return 'This reservation has processing history and must be retained for audit purposes.';
        }
        return null;
    }

    /** Update only unprocessed, unpaid pending requests; preserve customer and workflow fields. */
    public function updateByStaff(Venue $venue, array $attributes): void
    {
        DB::transaction(function () use ($venue, $attributes): void {
            // Match the existing workflow: lock venues before the reservation.
            $venues = Venue::whereIn('id', [$this->venue_id, $venue->id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $booking = self::query()->lockForUpdate()->findOrFail($this->getKey());
            if (! $venues->has($booking->venue_id)) {
                throw new DomainException('The reservation changed. Reload it before editing.');
            }
            if ($reason = $booking->staffEditBlockReason()) {
                throw new DomainException($reason);
            }
            $target = $venues->get($venue->id);
            if (! $target?->is_active) {
                throw new DomainException('The selected venue is not available for reservations.');
            }
            if (! $target->supportsEventType($attributes['event_type'])) {
                throw new DomainException('The selected venue does not support this event type.');
            }
            if ($attributes['guest_count'] > $target->capacity) {
                throw new DomainException('The guest count exceeds the selected venue capacity.');
            }
            if (! $attributes['starts_at']->isFuture() || $attributes['ends_at']->lessThanOrEqualTo($attributes['starts_at'])) {
                throw new DomainException('Select a future start time and a later end time.');
            }
            if (self::where('venue_id', $target->id)->whereKeyNot($booking->id)->active()
                ->overlapping($attributes['starts_at'], $attributes['ends_at'])->exists()) {
                throw new DomainException('This venue already has an active reservation during the selected time.');
            }
            // A different venue receives its current quote; otherwise retain the agreed quote.
            if ($booking->venue_id !== $target->id) {
                $attributes['quoted_price'] = $target->price;
            }
            $booking->update([...$attributes, 'venue_id' => $target->id]);
        }, 5);
        $this->refresh();
    }

    public function deleteByStaff(): void
    {
        DB::transaction(function (): void {
            Venue::query()->lockForUpdate()->findOrFail($this->venue_id);
            $booking = self::query()->lockForUpdate()->findOrFail($this->getKey());
            if ($booking->venue_id !== $this->venue_id) {
                throw new DomainException('The reservation changed. Reload it before deleting.');
            }
            if ($reason = $booking->staffDeleteBlockReason()) {
                throw new DomainException($reason);
            }
            $booking->delete();
        }, 5);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true)
            && $this->starts_at->isFuture();
    }
}
