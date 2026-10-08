<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'booking_id',
        'event_booking_id',
        'membership_purchase_id',
        'transaction_reference',
        'amount',
        'payment_method',
        'payment_date',
        'status',
        'reference_number',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function eventBooking(): BelongsTo
    {
        return $this->belongsTo(EventBooking::class);
    }

    public function membershipPurchase(): BelongsTo { return $this->belongsTo(MembershipPurchase::class); }

    public const CUSTOMER_METHODS = ['Card', 'ABA / KHQR', 'Cash at Hotel'];
    public const METHODS = ['Cash', 'Cash at Hotel', 'Card', 'ABA / KHQR'];

    public function purposeLabel(): string
    {
        return $this->membership_purchase_id ? 'Membership Purchase' : ($this->booking_id ? 'Hotel Booking' : 'Event Booking');
    }
}
