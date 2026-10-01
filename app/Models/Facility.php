<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Facility extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'location',
        'opening_time',
        'closing_time',
        'price',
        'status',
        'image',
    ];

    /**
     * A facility can be linked to multiple rooms as an amenity.
     */
    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'facility_room');
    }
}