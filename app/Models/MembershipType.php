<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'discount_percentage',
        'duration_months',
        'status',
        'price',
        'loyalty_upgrade_points',
        'next_membership_type_id',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'price' => 'decimal:2',
        'loyalty_upgrade_points' => 'integer',
    ];

    public function nextMembershipType(): BelongsTo { return $this->belongsTo(self::class, 'next_membership_type_id'); }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
