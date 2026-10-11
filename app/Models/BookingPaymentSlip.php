<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPaymentSlip extends Model
{
    protected $fillable = [
        'booking_id',
        'file_path',
        'original_filename',
        'mime_type',
        'reviewed',
    ];

    protected function casts(): array
    {
        return [
            'reviewed' => 'boolean',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** Protected route for displaying the slip to its owner or authorized staff. */
    public function url(): string
    {
        return route('booking-payment-slips.show', $this);
    }
}
