<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\MediaStorage;

class RoomImage extends Model
{
    protected $fillable = ['image_path', 'alt_text', 'sort_order', 'is_primary'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean', 'sort_order' => 'integer'];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function url(): string
    {
        return app(MediaStorage::class)->publicUrl($this->image_path);
    }
}
