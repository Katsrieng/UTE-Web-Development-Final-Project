<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'base_price',
        'capacity',
        'bed_type',
    ];

    /**
     * A room type has many individual rooms (e.g. Deluxe Suite has Room 101, Room 102).
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}