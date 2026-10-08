<?php

namespace App\Services;

use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTransaction;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\Payment;
use App\Models\User;

class LoyaltyService
{
    public function eligibleMembership(User $customer, bool $lock = false): ?Membership
    {
        if (! $customer->isCustomer() || ! $customer->is_active) { return null; }
        $query = Membership::where('user_id', $customer->id)->loyaltyPaid()->current()
            ->whereHas('membershipType', fn ($type) => $type->where('status', 'active'))
            ->orderByDesc('start_date')->orderByDesc('id');
        if ($lock) { $query->lockForUpdate(); }
        return $query->first();
    }

    /** Called inside the Paid transaction, after the customer's User and Payment rows are locked. */
    public function earn(Payment $payment): void
    {
        if ($payment->status !== 'Paid' || ! $payment->booking_id || $payment->membership_purchase_id || $payment->event_booking_id) { return; }
        if (LoyaltyTransaction::where('payment_id', $payment->id)->where('kind', 'earn')->exists()) { return; }
        $booking = $payment->booking;
        if (! $booking || (int) $booking->user_id !== (int) $payment->user_id || ! in_array($booking->status, \App\Models\Booking::ACTIVE_STATUSES, true)) { return; }
        $customer = User::findOrFail($payment->user_id);
        if (! $this->eligibleMembership($customer)) { return; }
        LoyaltyAccount::firstOrCreate(['user_id' => $customer->id], ['balance' => 0]);
        $account = LoyaltyAccount::where('user_id', $customer->id)->lockForUpdate()->firstOrFail();
        $membership = $this->eligibleMembership($customer, true);
        if (! $membership) { return; }
        $type = MembershipType::lockForUpdate()->find($membership->membership_type_id);
        if (! $type || $type->status !== 'active') { return; }
        [$dollars, $fraction] = explode('.', $payment->amount);
        $cents = (int) $dollars * 100 + (int) $fraction;
        $points = intdiv($cents, 100);
        $this->record($account, ['payment_id' => $payment->id, 'membership_id' => $membership->id, 'kind' => 'earn', 'points' => $points, 'source_amount_cents' => $cents]);
        $this->upgrade($account, $membership);
    }

    /** Refunds reverse the original entry even when membership is now expired or inactive. */
    public function reverse(Payment $payment): void
    {
        if ($payment->status !== 'Refunded') { return; }
        $earning = LoyaltyTransaction::where('payment_id', $payment->id)->where('kind', 'earn')->first();
        if (! $earning || LoyaltyTransaction::where('payment_id', $payment->id)->where('kind', 'refund')->exists()) { return; }
        $account = LoyaltyAccount::lockForUpdate()->findOrFail($earning->loyalty_account_id);
        $this->record($account, ['payment_id' => $payment->id, 'membership_id' => $earning->membership_id, 'kind' => 'refund',
            'points' => -$earning->points, 'source_amount_cents' => $earning->source_amount_cents]);
    }

    private function record(LoyaltyAccount $account, array $entry): void
    {
        $account->balance += $entry['points'];
        $account->transactions()->create(array_merge($entry, ['resulting_balance' => $account->balance]));
        $account->save();
    }

    private function upgrade(LoyaltyAccount $account, Membership $membership): void
    {
        $visited = [];
        while (true) {
            $type = MembershipType::lockForUpdate()->find($membership->membership_type_id);
            if (! $type || $type->status !== 'active' || ! $type->next_membership_type_id || ! $type->loyalty_upgrade_points) { return; }
            $visited[$type->id] = true;
            if ($account->balance < $type->loyalty_upgrade_points || isset($visited[$type->next_membership_type_id])) { return; }
            $next = MembershipType::lockForUpdate()->find($type->next_membership_type_id);
            if (! $next || $next->status !== 'active') { return; }
            $membership->update(['membership_type_id' => $next->id]);
            $this->record($account, ['membership_id' => $membership->id, 'kind' => 'upgrade', 'points' => -$type->loyalty_upgrade_points,
                'old_tier_name' => $type->name, 'new_tier_name' => $next->name]);
        }
    }

    public function summary(User $customer): array
    {
        $account = $customer->loyaltyAccount()->first();
        $eligible = $this->eligibleMembership($customer);
        $membership = $eligible
            ?? Membership::where('user_id', $customer->id)->loyaltyPaid()->with('membershipType')->orderByDesc('start_date')->orderByDesc('id')->first();
        $membership?->loadMissing('membershipType.nextMembershipType');
        $active = $eligible !== null;
        $balance = $account?->balance ?? 0;
        return ['account' => $account, 'membership' => $membership, 'active' => $active, 'balance' => $balance,
            'usable' => $active ? max(0, $balance) : 0,
            'activity' => $account ? $account->transactions()->with('payment.booking')->orderByDesc('id')->limit(5)->get() : collect()];
    }
}
