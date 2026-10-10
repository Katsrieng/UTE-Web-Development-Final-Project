<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    protected $fillable = [
        'name',
        'type',
        'description',
        'price',
        'status',
    ];

    public function bookingPackages(): HasMany
    {
        return $this->hasMany(BookingPackage::class);
    }

    /**
     * Bookings that include this package (many-to-many through booking_packages).
     * Requires Katsrieng's Booking model to exist with the reverse relation.
     */
    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class, 'booking_packages')
            ->withPivot('quantity', 'price')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
