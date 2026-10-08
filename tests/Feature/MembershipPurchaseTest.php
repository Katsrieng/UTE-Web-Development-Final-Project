<?php

namespace Tests\Feature;

use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipPurchaseTest extends TestCase
{
    use RefreshDatabase;
    private User $customer;
    private MembershipType $type;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-09');
        $this->customer = User::factory()->create();
        $this->type = MembershipType::create(['name' => 'Gold', 'price' => 40, 'discount_percentage' => 10, 'duration_months' => 12, 'loyalty_upgrade_points' => 700, 'status' => 'active']);
        $this->actingAs($this->customer);
    }

    private function buy(string $method = 'Card')
    {
        return $this->post(route('customer.payments.membership.store', $this->type), ['payment_method' => $method, 'transaction_reference' => 'DEMO-REFERENCE', 'amount' => 1, 'status' => 'Paid', 'user_id' => 999]);
    }

    public function test_card_simulation_activates_once_and_snapshots_price(): void
    {
        $this->buy()->assertSessionHasNoErrors()->assertRedirect();
        $this->buy()->assertSessionHasErrors('membership');
        $this->assertDatabaseCount('memberships', 1);
        $this->assertDatabaseCount('membership_purchases', 1);
        $this->assertDatabaseCount('payments', 1);
        $membership = Membership::sole();
        $this->assertSame('2026-10-09', $membership->start_date->toDateString());
        $this->assertSame('2027-10-09', $membership->end_date->toDateString());
        $this->assertSame('40.00', Payment::sole()->amount);
        $this->assertDatabaseHas('membership_purchases', ['status' => 'completed', 'membership_name' => 'Gold', 'price' => 40, 'purchase_type' => 'new']);
    }

    public function test_manual_payment_waits_for_staff_and_preserves_purchase_price(): void
    {
        $this->buy('ABA / KHQR')->assertSessionHasNoErrors();
        $this->buy('ABA / KHQR')->assertSessionHasNoErrors();
        $this->assertDatabaseCount('memberships', 0);
        $this->assertDatabaseCount('membership_purchases', 1);
        $this->type->update(['price' => 99]);
        $payment = Payment::sole();
        $this->actingAs(User::factory()->staff()->create())->post(route('payments.verify', $payment))->assertSessionHasNoErrors();
        $this->post(route('payments.verify', $payment))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('memberships', 1);
        $this->assertSame('40.00', $payment->fresh()->amount);
        $this->post(route('payments.refund', $payment))->assertRedirect();
        $this->assertSame('cancelled', Membership::sole()->status);
        $this->assertDatabaseHas('membership_purchases', ['status' => 'refunded']);
    }

    public function test_inactive_unpriced_or_existing_active_membership_cannot_be_purchased(): void
    {
        $this->type->update(['status' => 'inactive']);
        $this->buy()->assertSessionHasErrors('membership');
        $this->type->update(['status' => 'active', 'price' => null]);
        $this->buy()->assertSessionHasErrors('membership');
        $this->type->update(['price' => 40]);
        Membership::create(['user_id' => $this->customer->id, 'membership_type_id' => $this->type->id, 'start_date' => today()->addDay(), 'end_date' => today()->addYear(), 'status' => 'active']);
        $this->buy()->assertSessionHasErrors('membership');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_cancelled_pending_purchase_can_be_retried_without_activation(): void
    {
        $this->buy('Cash at Hotel');
        $payment = Payment::sole();
        $this->actingAs(User::factory()->staff()->create())->delete(route('payments.destroy', $payment))->assertRedirect();
        $this->assertDatabaseHas('membership_purchases', ['status' => 'cancelled']);
        $this->actingAs($this->customer);
        $this->buy('Card')->assertSessionHasNoErrors();
        $this->assertDatabaseCount('memberships', 1);
    }

    public function test_deactivated_plan_cannot_be_activated_by_staff_verification(): void
    {
        $this->buy('Cash at Hotel');
        $this->type->update(['status' => 'inactive']);
        $payment = Payment::sole();
        $this->actingAs(User::factory()->staff()->create())->post(route('payments.verify', $payment))->assertSessionHasErrors('membership');
        $this->assertSame('Pending', $payment->fresh()->status);
        $this->assertDatabaseCount('memberships', 0);
    }

    public function test_membership_checkout_and_linked_staff_edit_views_render(): void
    {
        $this->get(route('memberships.index'))->assertOk()->assertSee(route('customer.payments.membership', $this->type));
        $this->get(route('customer.payments.membership', $this->type))->assertOk()->assertSee('Gold Membership')->assertSee('$40.00');
        $this->buy('Cash at Hotel');
        $payment = Payment::sole();
        $this->get(route('memberships.index'))->assertOk()->assertSee('purchase is pending');
        $this->get(route('customer.payments.receipt', $payment))->assertOk()->assertSee('Gold Membership');
        $this->actingAs(User::factory()->staff()->create())->get(route('payments.edit', $payment))->assertOk()->assertSee('Membership Purchase');
    }

    public function test_membership_activation_failure_rolls_back_payment_and_purchase(): void
    {
        \Illuminate\Support\Facades\Event::listen('eloquent.creating: '.Membership::class, fn () => throw new \RuntimeException('Simulated activation failure'));
        try { $this->buy()->assertServerError(); }
        finally { \Illuminate\Support\Facades\Event::forget('eloquent.creating: '.Membership::class); }
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('membership_purchases', 0);
        $this->assertDatabaseCount('memberships', 0);
    }
}
