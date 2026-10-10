<?php

namespace Tests\Feature;

use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_pays_joins_sees_and_cancels_membership(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class); // seeding twice must not duplicate tiers
        $this->assertSame(3, MembershipType::count());

        $customer = User::where('email', 'customer1@hotel.com')->first();
        $gold = MembershipType::where('name', 'Gold')->first();

        $this->actingAs($customer)->get("/memberships/{$gold->id}/checkout")->assertOk()->assertSee('Pay $199.00');

        $this->actingAs($customer)->post('/memberships/subscribe', ['membership_type_id' => $gold->id])
            ->assertSessionHasErrors('payment_method');
        $this->assertSame(0, Membership::count());

        $this->actingAs($customer)->post('/memberships/subscribe', ['membership_type_id' => $gold->id, 'payment_method' => 'Card'])
            ->assertRedirect('/memberships');
        $payment = Payment::firstOrFail();
        $this->assertSame('Paid', $payment->status);
        $this->assertSame('199.00', (string) $payment->amount);

        // A second submit must not create another membership or payment.
        $this->actingAs($customer)->post('/memberships/subscribe', ['membership_type_id' => $gold->id, 'payment_method' => 'Card']);
        $this->assertSame(1, Membership::count());
        $this->assertSame(1, Payment::count());

        $customer = $customer->fresh();
        $this->actingAs($customer)->get('/memberships')->assertSee('Gold Member')->assertSee('Cancel Membership');
        $this->actingAs($customer)->get('/profile')->assertSee('Gold Member');

        $membership = Membership::firstOrFail();
        $other = User::where('email', 'customer2@hotel.com')->first();
        $this->actingAs($other)->post("/memberships/{$membership->id}/cancel")->assertForbidden();

        $this->actingAs($customer)->post("/memberships/{$membership->id}/cancel")->assertRedirect('/memberships');
        $this->assertSame('cancelled', $membership->fresh()->status);
    }

    public function test_refunding_a_membership_payment_cancels_the_membership(): void
    {
        $this->seed(DatabaseSeeder::class);

        $customer = User::where('email', 'customer1@hotel.com')->first();
        $admin = User::where('email', 'admin@hotel.com')->first();
        $silver = MembershipType::where('name', 'Silver')->first();

        $this->actingAs($customer)->post('/memberships/subscribe', ['membership_type_id' => $silver->id, 'payment_method' => 'Bank Transfer']);
        $payment = Payment::firstOrFail();

        $this->actingAs($admin)->get('/payments')->assertOk()->assertSee('Silver membership');
        $this->actingAs($admin)->post("/payments/{$payment->id}/refund")->assertRedirect();

        $this->assertSame('Refunded', $payment->fresh()->status);
        $this->assertSame('cancelled', Membership::firstOrFail()->status);
    }
}
