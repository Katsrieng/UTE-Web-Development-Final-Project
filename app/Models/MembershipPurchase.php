<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MembershipPurchase extends Model
{
    protected $fillable = ['user_id', 'membership_type_id', 'membership_id', 'purchase_type', 'membership_name', 'price', 'status'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    public function membershipType(): BelongsTo { return $this->belongsTo(MembershipType::class); }
    public function membership(): BelongsTo { return $this->belongsTo(Membership::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function payment(): HasOne { return $this->hasOne(Payment::class); }
}
