<?php

namespace App\Models;

use Database\Factories\VenueFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venue extends Model
{
    /** @use HasFactory<VenueFactory> */
    use HasFactory;

    public const EVENT_TYPE_WEDDING = 'wedding';

    public const EVENT_TYPE_MEETING = 'meeting';

    public const EVENT_TYPE_PARTY = 'party';

    public const EVENT_TYPES = [
        self::EVENT_TYPE_WEDDING,
        self::EVENT_TYPE_MEETING,
        self::EVENT_TYPE_PARTY,
    ];

    protected $fillable = [
        'name',
        'description',
        'location',
        'capacity',
        'price',
        'event_types',
        'images',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'price' => 'decimal:2',
            'event_types' => 'array',
            'images' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function eventBookings(): HasMany
    {
        return $this->hasMany(EventBooking::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function supportsEventType(string $eventType): bool
    {
        return in_array($eventType, $this->event_types ?? [], true);
    }
}
