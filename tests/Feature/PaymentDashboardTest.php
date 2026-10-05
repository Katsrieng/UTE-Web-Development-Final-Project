<?php

namespace Tests\Feature;

use App\Models\EventBooking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PaymentDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_dashboard(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_staff_can_access_dashboard(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_dashboard_calculates_payment_statistics_correctly(): void
    {
        $customer = User::factory()->create();
        $staff = User::factory()->staff()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();

        Payment::create([
            'user_id' => $customer->id,
            'booking_id' => 123,
            'event_booking_id' => null,
            'amount' => 150.00,
            'payment_method' => 'Cash',
            'payment_date' => '2026-10-01',
            'status' => 'Paid',
            'reference_number' => 'PAY-DASH-ROOM-PAID',
        ]);

        Payment::create([
            'user_id' => $customer->id,
            'booking_id' => null,
            'event_booking_id' => $eventBooking->id,
            'amount' => 250.00,
            'payment_method' => 'Card',
            'payment_date' => '2026-10-02',
            'status' => 'Paid',
            'reference_number' => 'PAY-DASH-EVENT-PAID',
        ]);

        Payment::create([
            'user_id' => $customer->id,
            'booking_id' => null,
            'event_booking_id' => $eventBooking->id,
            'amount' => 80.00,
            'payment_method' => 'Bank Transfer',
            'payment_date' => '2026-10-03',
            'status' => 'Pending',
            'reference_number' => 'PAY-DASH-EVENT-PENDING',
        ]);

        Payment::create([
            'user_id' => $customer->id,
            'booking_id' => 124,
            'event_booking_id' => null,
            'amount' => 40.00,
            'payment_method' => 'Cash',
            'payment_date' => '2026-10-04',
            'status' => 'Refunded',
            'reference_number' => 'PAY-DASH-ROOM-REFUNDED',
        ]);

        $response = $this->actingAs($staff)->get(route('dashboard'));

        $response->assertOk();
        $this->assertEqualsWithDelta(400.00, (float) $response->viewData('totalRevenue'), 0.001);
        $this->assertSame(4, $response->viewData('totalPayments'));
        $this->assertSame(2, $response->viewData('paidPayments'));
        $this->assertSame(1, $response->viewData('pendingPayments'));
        $this->assertSame(1, $response->viewData('refundedPayments'));
        $this->assertEqualsWithDelta(150.00, (float) $response->viewData('roomBookingRevenue'), 0.001);
        $this->assertEqualsWithDelta(250.00, (float) $response->viewData('eventBookingRevenue'), 0.001);
    }

    public function test_dashboard_returns_only_the_latest_five_payments(): void
    {
        $customer = User::factory()->create();
        $staff = User::factory()->staff()->create();

        for ($number = 1; $number <= 6; $number++) {
            $payment = new Payment([
                'user_id' => $customer->id,
                'booking_id' => 200 + $number,
                'event_booking_id' => null,
                'amount' => 10.00 * $number,
                'payment_method' => 'Cash',
                'payment_date' => '2026-10-01',
                'status' => 'Paid',
                'reference_number' => sprintf('PAY-DASH-RECENT-%02d', $number),
            ]);
            $payment->created_at = now()->subDays(6 - $number);
            $payment->save();
        }

        $response = $this->actingAs($staff)->get(route('dashboard'));

        $response->assertOk();
        $recentPayments = $response->viewData('recentPayments');
        $references = $recentPayments->pluck('reference_number')->all();

        $this->assertCount(5, $recentPayments);
        $this->assertNotContains('PAY-DASH-RECENT-01', $references);
        $this->assertSame([
            'PAY-DASH-RECENT-06',
            'PAY-DASH-RECENT-05',
            'PAY-DASH-RECENT-04',
            'PAY-DASH-RECENT-03',
            'PAY-DASH-RECENT-02',
        ], $references);
    }
}
