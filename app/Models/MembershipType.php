<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'discount_percentage',
        'duration_months',
        'status',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
    ];

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
