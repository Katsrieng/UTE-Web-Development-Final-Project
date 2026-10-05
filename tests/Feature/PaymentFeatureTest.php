<?php

namespace Tests\Feature;

use App\Models\EventBooking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PaymentFeatureTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_cannot_access_payments(): void
    {
        $this->get(route('payments.index'))->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_payment_management(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('payments.index'))
            ->assertForbidden();
    }

    public function test_staff_can_access_payment_management(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->get(route('payments.index'))
            ->assertOk();
    }

    public function test_staff_can_create_a_valid_event_payment(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();

        $response = $this->actingAs($staff)
            ->post(route('payments.store'), $this->validPayload($customer, $eventBooking));

        $response->assertRedirect(route('payments.index'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('payments', [
            'user_id' => $customer->id,
            'booking_id' => null,
            'event_booking_id' => $eventBooking->id,
            'reference_number' => 'PAY-FEATURE-001',
        ]);
    }

    public function test_payment_rejects_a_nonexistent_user(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();

        $response = $this->actingAs($staff)
            ->post(route('payments.store'), $this->validPayload($customer, $eventBooking, [
                'user_id' => 999999,
            ]));

        $response->assertSessionHasErrors('user_id');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_rejects_a_nonexistent_event_booking(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();

        $response = $this->actingAs($staff)
            ->post(route('payments.store'), $this->validPayload($customer, $eventBooking, [
                'event_booking_id' => 999999,
            ]));

        $response->assertSessionHasErrors('event_booking_id');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_rejects_an_event_booking_owned_by_another_customer(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($otherCustomer)->create();
        $staff = User::factory()->staff()->create();

        $response = $this->actingAs($staff)
            ->post(route('payments.store'), $this->validPayload($customer, $eventBooking));

        $response->assertSessionHasErrors('event_booking_id');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_rejects_when_neither_booking_type_is_supplied(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();

        $response = $this->actingAs($staff)
            ->post(route('payments.store'), $this->validPayload($customer, $eventBooking, [
                'event_booking_id' => null,
            ]));

        $response->assertSessionHasErrors('booking_id');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_rejects_when_both_booking_types_are_supplied(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();

        $response = $this->actingAs($staff)
            ->post(route('payments.store'), $this->validPayload($customer, $eventBooking, [
                'booking_id' => 123,
            ]));

        $response->assertSessionHasErrors('booking_id');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_update_may_keep_the_existing_reference_number(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();
        $payment = Payment::create($this->validPayload($customer, $eventBooking));

        $response = $this->actingAs($staff)
            ->put(route('payments.update', $payment), $this->validPayload($customer, $eventBooking, [
                'amount' => 200.00,
            ]));

        $response->assertRedirect(route('payments.index'));
        $response->assertSessionHasNoErrors();
        $this->assertSame('PAY-FEATURE-001', $payment->fresh()->reference_number);
        $this->assertSame('200.00', $payment->fresh()->amount);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(User $customer, EventBooking $eventBooking, array $overrides = []): array
    {
        return array_merge([
            'user_id' => $customer->id,
            'booking_id' => null,
            'event_booking_id' => $eventBooking->id,
            'amount' => 150.00,
            'payment_method' => 'Card',
            'payment_date' => '2026-10-01',
            'status' => 'Paid',
            'reference_number' => 'PAY-FEATURE-001',
        ], $overrides);
    }
}
