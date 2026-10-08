<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPackage extends Model
{
    protected function casts(): array
    {
        return ['quantity' => 'integer', 'price' => 'decimal:2'];
    }

    protected $fillable = [
        'booking_id',
        'package_id',
        'quantity',
        'price',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
