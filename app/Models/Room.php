<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Room extends Model
{
    public function images(): HasMany
    {
        return $this->hasMany(RoomImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function coverImageUrl(): ?string
    {
        $cover = $this->images->firstWhere('is_primary', true) ?? $this->images->first();
        if ($cover) {
            return $cover->url();
        }

        return $this->image
            ? (str_starts_with($this->image, 'images/') ? asset($this->image) : Storage::disk('public')->url($this->image))
            : null;
    }

    use HasFactory;

    protected $fillable = [
        'room_type_id',
        'room_number',
        'floor',
        'price_per_night',
        'status',
        'description',
        'image',
    ];

    /**
     * Each room belongs to a specific room type category.
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * Each room can have multiple amenities/facilities (e.g., Mini Bar, Ocean View).
     */
    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(Facility::class, 'facility_room');
    }
}
