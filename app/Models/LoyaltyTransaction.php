<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyTransaction extends Model
{
    protected $fillable = ['loyalty_account_id', 'payment_id', 'membership_id', 'kind', 'points', 'resulting_balance', 'source_amount_cents', 'old_tier_name', 'new_tier_name'];
    protected function casts(): array { return ['points' => 'integer', 'resulting_balance' => 'integer', 'source_amount_cents' => 'integer']; }
    public function account(): BelongsTo { return $this->belongsTo(LoyaltyAccount::class, 'loyalty_account_id'); }
    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
    public function membership(): BelongsTo { return $this->belongsTo(Membership::class); }
}
