<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTransaction;
use App\Models\Membership;
use App\Models\MembershipPurchase;
use App\Models\MembershipType;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LoyaltyTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $staff;
    private Room $room;
    private MembershipType $silver;
    private MembershipType $gold;
    private MembershipType $platinum;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grantStaffPermissions('refund_payments', 'view_loyalty_activity');
        $this->travelTo('2026-10-09');
        $this->customer = User::factory()->create();
        $this->staff = User::factory()->staff()->create();
        $this->platinum = MembershipType::create(['name' => 'Platinum', 'price' => 100, 'discount_percentage' => 15, 'duration_months' => 12, 'status' => 'active']);
        $this->gold = MembershipType::create(['name' => 'Gold', 'price' => 60, 'discount_percentage' => 10, 'duration_months' => 12, 'status' => 'active', 'loyalty_upgrade_points' => 700, 'next_membership_type_id' => $this->platinum->id]);
        $this->silver = MembershipType::create(['name' => 'Silver', 'price' => 30, 'discount_percentage' => 5, 'duration_months' => 12, 'status' => 'active', 'loyalty_upgrade_points' => 300, 'next_membership_type_id' => $this->gold->id]);
        $type = RoomType::create(['name' => 'Suite', 'capacity' => 2, 'base_price' => 75]);
        $this->room = Room::create(['room_type_id' => $type->id, 'room_number' => 'LOYALTY-101', 'price_per_night' => 75, 'status' => 'available']);
    }

    private function member(?MembershipType $type = null): Membership
    {
        app(PaymentService::class)->purchaseMembership($this->customer, ($type ?? $this->silver)->id, ['payment_method' => 'Card']);
        return Membership::where('user_id', $this->customer->id)->orderByDesc('id')->firstOrFail();
    }

    private function booking(string $amount): Booking
    {
        return Booking::create(['user_id' => $this->customer->id, 'room_id' => $this->room->id, 'check_in_date' => today()->addDay(),
            'check_out_date' => today()->addDays(3), 'number_of_guests' => 2, 'status' => 'Pending', 'total_amount' => $amount]);
    }

    private function pay(string $amount, string $method = 'Card'): Payment
    {
        return app(PaymentService::class)->payBooking($this->customer, $this->booking($amount)->id, ['payment_method' => $method]);
    }

    public static function amounts(): array
    {
        return [['74.00', 74], ['74.50', 74], ['74.99', 74], ['75.00', 75], ['0.99', 0]];
    }

    #[DataProvider('amounts')]
    public function test_earning_uses_whole_dollars_of_authoritative_paid_amount(string $amount, int $points): void
    {
        $this->member();
        $payment = $this->pay($amount);
        $this->assertSame($points, LoyaltyAccount::sole()->balance);
        $this->assertDatabaseHas('loyalty_transactions', ['payment_id' => $payment->id, 'kind' => 'earn', 'points' => $points]);
        $this->assertDatabaseCount('loyalty_transactions', 1);
        $this->assertSame('Confirmed', $payment->booking->status);
    }

    public function test_pending_then_paid_and_duplicate_verification_earn_once(): void
    {
        $this->member();
        $payment = $this->pay('85.50', 'Cash at Hotel');
        $this->assertDatabaseCount('loyalty_transactions', 0);
        app(PaymentService::class)->verify($payment, $this->staff);
        app(PaymentService::class)->verify($payment, $this->staff);
        $this->assertSame(85, LoyaltyAccount::sole()->balance);
        $this->assertDatabaseCount('loyalty_transactions', 1);
        $this->assertDatabaseCount('booking_status_logs', 1);
    }

    public function test_refund_reverses_only_original_earning_once_and_never_downgrades(): void
    {
        $membership = $this->member();
        $payment = $this->pay('340.00');
        $this->assertSame($this->gold->id, $membership->fresh()->membership_type_id);
        $this->assertSame(40, LoyaltyAccount::sole()->balance);
        $this->actingAs($this->staff)->post(route('payments.refund', $payment))->assertSessionHas('success');
        $this->assertSame(-300, LoyaltyAccount::sole()->balance);
        $this->assertSame($this->gold->id, $membership->fresh()->membership_type_id);
        $this->assertDatabaseHas('loyalty_transactions', ['payment_id' => $payment->id, 'kind' => 'refund', 'points' => -340]);
        $this->post(route('payments.refund', $payment));
        $this->assertDatabaseCount('loyalty_transactions', 3);
        $this->pay('350.00');
        $this->assertSame(50, LoyaltyAccount::sole()->balance);
        $this->assertSame($this->gold->id, $membership->fresh()->membership_type_id);
    }

    public function test_gold_to_platinum_consumes_700_and_preserves_dates(): void
    {
        $membership = $this->member($this->gold);
        $dates = [$membership->start_date->toDateString(), $membership->end_date->toDateString()];
        $this->pay('740.00');
        $membership->refresh();
        $this->assertSame($this->platinum->id, $membership->membership_type_id);
        $this->assertSame(40, LoyaltyAccount::sole()->balance);
        $this->assertSame($dates, [$membership->start_date->toDateString(), $membership->end_date->toDateString()]);
        $this->assertDatabaseHas('loyalty_transactions', ['kind' => 'upgrade', 'points' => -700, 'old_tier_name' => 'Gold', 'new_tier_name' => 'Platinum']);
    }

    public function test_sequential_upgrades_preserve_leftovers_and_purchase_booking_snapshots(): void
    {
        $membership = $this->member();
        $purchase = MembershipPurchase::sole();
        $booking = $this->booking('1040.00');
        $booking->update(['membership_id' => $membership->id, 'membership_name' => 'Silver', 'membership_discount_percentage' => 5, 'membership_discount_amount' => 10]);
        app(PaymentService::class)->payBooking($this->customer, $booking->id, ['payment_method' => 'Card']);
        $this->assertSame($this->platinum->id, $membership->fresh()->membership_type_id);
        $this->assertSame(40, LoyaltyAccount::sole()->balance);
        $this->assertCount(2, LoyaltyTransaction::where('kind', 'upgrade')->get());
        $this->assertSame('30.00', $purchase->fresh()->price);
        $this->assertSame('Silver', $purchase->fresh()->membership_name);
        $this->assertSame('Silver', $booking->fresh()->membership_name);
        $this->assertEquals(5, $booking->fresh()->membership_discount_percentage);
    }

    public function test_expiry_freezes_balance_and_blocks_earning_and_upgrades(): void
    {
        $membership = $this->member();
        $this->pay('240.00');
        $membership->update(['end_date' => today()->subDay()]);
        $this->pay('100.00');
        $this->assertSame(240, LoyaltyAccount::sole()->balance);
        $this->assertSame($this->silver->id, $membership->fresh()->membership_type_id);
        $this->actingAs($this->customer)->get(route('memberships.index'))->assertOk()->assertSee('Points frozen until membership renewal')->assertSee('240');
    }

    public function test_legacy_membership_without_paid_purchase_never_earns(): void
    {
        Membership::create(['user_id' => $this->customer->id, 'membership_type_id' => $this->silver->id, 'start_date' => today(), 'end_date' => today()->addYear(), 'status' => 'active']);
        $this->pay('350.00');
        $this->assertDatabaseCount('loyalty_accounts', 0);
        $this->assertDatabaseCount('loyalty_transactions', 0);
    }

    public function test_membership_and_event_payments_do_not_earn(): void
    {
        $this->member();
        $event = \App\Models\EventBooking::factory()->for($this->customer)->create();
        $payment = Payment::create(['user_id' => $this->customer->id, 'event_booking_id' => $event->id, 'amount' => 500, 'payment_method' => 'Cash', 'payment_date' => today(), 'status' => 'Pending', 'reference_number' => 'LOYALTY-EVENT']);
        app(PaymentService::class)->verify($payment, $this->staff);
        $this->assertDatabaseCount('loyalty_accounts', 0);
        $this->assertDatabaseCount('loyalty_transactions', 0);
    }

    public function test_packages_and_discounted_booking_total_are_the_earning_source(): void
    {
        $this->member();
        $package = \App\Models\Package::create(['name' => 'Extra', 'type' => 'other', 'price' => 10, 'status' => 'active']);
        $booking = app(\App\Services\BookingService::class)->create(['user_id' => $this->customer->id, 'room_id' => $this->room->id,
            'check_in_date' => today()->addDay()->toDateString(), 'check_out_date' => today()->addDays(3)->toDateString(),
            'number_of_guests' => 2, 'packages' => [['package_id' => $package->id, 'quantity' => 2]]]);
        $payment = app(PaymentService::class)->payBooking($this->customer, $booking->id, ['payment_method' => 'Card']);
        $this->assertSame('161.50', $payment->amount);
        $this->assertSame(161, LoyaltyAccount::sole()->balance);
        $this->assertDatabaseHas('loyalty_transactions', ['payment_id' => $payment->id, 'source_amount_cents' => 16150, 'points' => 161]);
    }

    public static function ineligibleStates(): array
    {
        return [['future'], ['expired'], ['cancelled'], ['inactive_type'], ['purchase_refunded'], ['payment_pending'], ['inactive_customer']];
    }

    #[DataProvider('ineligibleStates')]
    public function test_paid_membership_eligibility_is_rechecked_when_payment_becomes_paid(string $state): void
    {
        $membership = $this->member();
        $payment = $this->pay('350.00', 'Cash at Hotel');
        match ($state) {
            'future' => $membership->update(['start_date' => today()->addDay()]),
            'expired' => $membership->update(['end_date' => today()->subDay()]),
            'cancelled' => $membership->update(['status' => 'cancelled']),
            'inactive_type' => $this->silver->update(['status' => 'inactive']),
            'purchase_refunded' => MembershipPurchase::sole()->update(['status' => 'refunded']),
            'payment_pending' => MembershipPurchase::sole()->payment->update(['status' => 'Pending']),
            'inactive_customer' => $this->customer->update(['is_active' => false]),
        };
        app(PaymentService::class)->verify($payment, $this->staff);
        $this->assertDatabaseCount('loyalty_transactions', 0);
        $this->assertSame($this->silver->id, $membership->fresh()->membership_type_id);
    }

    public function test_customer_and_staff_views_show_progress_and_refund_adjustment_without_edit_controls(): void
    {
        $this->member();
        $payment = $this->pay('340.00');
        $this->actingAs($this->customer)->get(route('memberships.index'))->assertOk()->assertSee('Gold Member')->assertSee('660 points to Platinum');
        $this->actingAs($this->staff)->get(route('payments.show', $payment))->assertOk()->assertSee('Customer loyalty')->assertSee($payment->reference_number);
        $this->post(route('payments.refund', $payment));
        $this->actingAs($this->customer)->get(route('memberships.index'))->assertOk()->assertSee('Refund adjustment balance: -300 points')->assertSee('Gold Member')->assertDontSee('Frozen rewards');
        $this->actingAs(User::factory()->create())->get(route('memberships.index'))->assertOk()->assertDontSee('Refund adjustment balance');
    }

    public function test_cancelled_booking_cannot_be_paid_or_earn_and_legacy_paid_records_are_not_backfilled(): void
    {
        $this->member();
        $booking = $this->booking('75.00');
        $booking->update(['status' => 'Cancelled']);
        try { app(PaymentService::class)->payBooking($this->customer, $booking->id, ['payment_method' => 'Card']); $this->fail('Expected cancelled payment rejection'); }
        catch (\Illuminate\Validation\ValidationException $exception) { $this->assertArrayHasKey('payment', $exception->errors()); }
        $booking->update(['status' => 'Confirmed']);
        $payment = Payment::create(['user_id' => $this->customer->id, 'booking_id' => $booking->id, 'amount' => 75, 'payment_method' => 'Card', 'payment_date' => today(), 'status' => 'Paid', 'reference_number' => 'LOYALTY-LEGACY']);
        app(PaymentService::class)->verify($payment, $this->staff);
        $this->assertDatabaseCount('loyalty_transactions', 0);
    }

    public function test_refund_after_expiry_still_reverses_earning_and_renewal_keeps_the_account(): void
    {
        $membership = $this->member();
        $payment = $this->pay('75.00');
        $membership->update(['end_date' => today()->subDay()]);
        $this->actingAs($this->staff)->post(route('payments.refund', $payment));
        $this->assertSame(0, LoyaltyAccount::sole()->balance);
        $this->member($this->gold);
        $this->assertDatabaseCount('loyalty_accounts', 1);
        $this->pay('40.00');
        $this->assertSame(40, LoyaltyAccount::sole()->balance);
    }

    public function test_progression_uses_relationships_after_tier_is_renamed_and_does_not_repeat(): void
    {
        $membership = $this->member();
        $this->silver->update(['name' => 'Ocean']);
        $payment = $this->pay('300.00');
        app(PaymentService::class)->verify($payment, $this->staff);
        $this->assertSame(0, LoyaltyAccount::sole()->balance);
        $this->assertSame($this->gold->id, $membership->fresh()->membership_type_id);
        $this->assertDatabaseHas('loyalty_transactions', ['kind' => 'upgrade', 'old_tier_name' => 'Ocean', 'new_tier_name' => 'Gold', 'points' => -300]);
        $this->assertSame(1, LoyaltyTransaction::where('kind', 'upgrade')->count());
    }

    public function test_upgrade_history_failure_rolls_back_tier_payment_and_earning(): void
    {
        $membership = $this->member();
        $booking = $this->booking('340.00');
        \Illuminate\Support\Facades\Event::listen('eloquent.creating: '.LoyaltyTransaction::class, function ($entry) {
            if ($entry->kind === 'upgrade') { throw new \RuntimeException('Upgrade history failure'); }
        });
        try { app(PaymentService::class)->payBooking($this->customer, $booking->id, ['payment_method' => 'Card']); $this->fail('Expected upgrade failure'); }
        catch (\RuntimeException $exception) { $this->assertSame('Upgrade history failure', $exception->getMessage()); }
        $this->assertSame($this->silver->id, $membership->fresh()->membership_type_id);
        $this->assertSame('Pending', $booking->fresh()->status);
        $this->assertDatabaseMissing('payments', ['booking_id' => $booking->id]);
        $this->assertDatabaseCount('loyalty_transactions', 0);
    }

    public function test_refund_ledger_failure_rolls_back_payment_refund_and_balance(): void
    {
        $this->member();
        $payment = $this->pay('75.00');
        \Illuminate\Support\Facades\Event::listen('eloquent.creating: '.LoyaltyTransaction::class, function ($entry) {
            if ($entry->kind === 'refund') { throw new \RuntimeException('Refund history failure'); }
        });
        $this->withoutExceptionHandling();
        try { $this->actingAs($this->staff)->post(route('payments.refund', $payment)); $this->fail('Expected refund failure'); }
        catch (\RuntimeException $exception) { $this->assertSame('Refund history failure', $exception->getMessage()); }
        $this->assertSame('Paid', $payment->fresh()->status);
        $this->assertSame(75, LoyaltyAccount::sole()->balance);
        $this->assertDatabaseCount('loyalty_transactions', 1);
    }

    public function test_database_rejects_duplicate_earning_entries_for_one_payment(): void
    {
        $this->member();
        $this->pay('75.00');
        $entry = LoyaltyTransaction::sole();
        $this->expectException(\Illuminate\Database\QueryException::class);
        $entry->replicate()->save();
    }

    public function test_customer_with_audited_loyalty_account_cannot_be_deleted(): void
    {
        $this->member();
        $this->pay('75.00');
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->delete(route('management.customers.destroy', $this->customer))->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->customer->id]);
        $this->assertDatabaseCount('loyalty_transactions', 1);
    }

    public function test_ledger_failure_rolls_back_payment_confirmation_and_points(): void
    {
        $this->member();
        $payment = $this->pay('75.00', 'Cash at Hotel');
        \Illuminate\Support\Facades\Event::listen('eloquent.creating: '.LoyaltyTransaction::class, fn () => throw new \RuntimeException('Ledger failure'));
        try { app(PaymentService::class)->verify($payment, $this->staff); $this->fail('Expected ledger failure'); }
        catch (\RuntimeException $exception) { $this->assertSame('Ledger failure', $exception->getMessage()); }
        $this->assertSame('Pending', $payment->fresh()->status);
        $this->assertSame('Pending', $payment->booking->fresh()->status);
        $this->assertDatabaseCount('loyalty_accounts', 0);
        $this->assertDatabaseCount('booking_status_logs', 0);
    }
}
