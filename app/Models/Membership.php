<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function purchases(): HasMany { return $this->hasMany(MembershipPurchase::class); }
    public function loyaltyTransactions(): HasMany { return $this->hasMany(LoyaltyTransaction::class); }

    public function scopeLoyaltyPaid(Builder $query): Builder
    {
        return $query->whereHas('purchases', fn (Builder $purchases) => $purchases
            ->where('status', 'completed')->whereColumn('membership_purchases.user_id', 'memberships.user_id')
            ->whereHas('payment', fn (Builder $payments) => $payments->where('status', 'Paid')
                ->whereColumn('payments.user_id', 'membership_purchases.user_id')));
    }

    /**
     * Membership dates are inclusive calendar dates in the application's timezone.
     */
    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->start_date->toDateString() <= today()->toDateString()
            && $this->end_date->toDateString() >= today()->toDateString();
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today());
    }

    public function scopeDiscountEligible(Builder $query): Builder
    {
        // Existing active records remain eligible legacy/test memberships. New public activation is blocked.
        return $query->current()->whereHas('membershipType', fn (Builder $types) => $types
            ->where('status', 'active')->where('discount_percentage', '>=', 0)->where('discount_percentage', '<=', 100));
    }

    /**
     * Discount percentage to apply to a booking, 0 if membership isn't active.
     */
    public function discountPercentage(): float
    {
        if (! $this->isActive() || ! $this->membershipType || $this->membershipType->status !== 'active') {
            return 0.0;
        }

        $percentage = (float) $this->membershipType->discount_percentage;

        return $percentage >= 0 && $percentage <= 100 ? $percentage : 0.0;
    }

    /**
     * Flip status to 'expired' if the end date has passed. Call this from
     * a scheduled command, or lazily whenever a membership is loaded.
     */
    public function refreshExpiry(): void
    {
        if ($this->status === 'active' && $this->end_date->toDateString() < today()->toDateString()) {
            $this->status = 'expired';
            $this->save();
        }
    }
}
