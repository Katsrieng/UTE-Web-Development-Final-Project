<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Membership extends Model
{
    protected $fillable = [
        'user_id',
        'membership_type_id',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function membershipType(): BelongsTo
    {
        return $this->belongsTo(MembershipType::class);
    }

    /**
     * True if the membership is marked active AND not past its end date.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->end_date->isFuture();
    }

    /**
     * Discount percentage to apply to a booking, 0 if membership isn't active.
     */
    public function discountPercentage(): float
    {
        if (! $this->isActive()) {
            return 0.0;
        }

        return (float) $this->membershipType->discount_percentage;
    }

    /**
     * Flip status to 'expired' if the end date has passed. Call this from
     * a scheduled command, or lazily whenever a membership is loaded.
     */
    public function refreshExpiry(): void
    {
        if ($this->status === 'active' && $this->end_date->isPast()) {
            $this->status = 'expired';
            $this->save();
        }
    }
}
