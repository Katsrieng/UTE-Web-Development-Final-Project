<?php

namespace Tests\Feature;

use App\Models\EventBooking;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EventReservationWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_customer_can_submit_and_view_a_pending_reservation(): void
    {
        $this->travelTo('2026-10-05 09:00:00');
        $customer = User::factory()->create();
        $venue = Venue::factory()->create([
            'capacity' => 100,
            'price' => 750,
            'event_types' => [Venue::EVENT_TYPE_WEDDING],
        ]);

        $response = $this->actingAs($customer)->post(route('event-reservations.store'), [
            'venue_id' => $venue->id,
            'event_type' => Venue::EVENT_TYPE_WEDDING,
            'event_date' => '2026-10-20',
            'start_time' => '10:00',
            'end_time' => '15:00',
            'guest_count' => 80,
            'special_requests' => 'Vegetarian menu and wheelchair access.',
        ]);

        $eventBooking = EventBooking::query()->sole();

        $response->assertRedirect(route('event-reservations.show', $eventBooking));
        $this->assertDatabaseHas('event_bookings', [
            'id' => $eventBooking->id,
            'user_id' => $customer->id,
            'venue_id' => $venue->id,
            'status' => EventBooking::STATUS_PENDING,
            'guest_count' => 80,
            'quoted_price' => 750,
        ]);

        $this->actingAs($customer)
            ->get(route('event-reservations.show', $eventBooking))
            ->assertSee('Vegetarian menu and wheelchair access.');
    }

    public function test_staff_can_approve_a_pending_reservation(): void
    {
        $this->travelTo('2026-10-05 09:00:00');
        $staff = User::factory()->staff()->create();
        $eventBooking = EventBooking::factory()->create([
            'starts_at' => '2026-10-20 10:00:00',
            'ends_at' => '2026-10-20 12:00:00',
        ]);

        $response = $this->actingAs($staff)->patch(
            route('management.event-reservations.approve', $eventBooking),
            ['status_note' => 'Setup confirmed with the events team.'],
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('event_bookings', [
            'id' => $eventBooking->id,
            'status' => EventBooking::STATUS_APPROVED,
            'processed_by' => $staff->id,
            'status_note' => 'Setup confirmed with the events team.',
        ]);
    }

    public function test_customer_can_cancel_own_future_active_reservation(): void
    {
        $this->travelTo('2026-10-05 09:00:00');
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->approved()->for($customer)->create([
            'starts_at' => '2026-10-20 10:00:00',
            'ends_at' => '2026-10-20 12:00:00',
        ]);

        $response = $this->actingAs($customer)->patch(
            route('event-reservations.cancel', $eventBooking),
        );

        $response->assertRedirect(route('event-reservations.show', $eventBooking));
        $this->assertDatabaseHas('event_bookings', [
            'id' => $eventBooking->id,
            'status' => EventBooking::STATUS_CANCELLED,
            'processed_by' => $customer->id,
        ]);
        $this->assertNotNull($eventBooking->fresh()->cancelled_at);
    }

    public function test_staff_cannot_approve_reservation_after_venue_capacity_is_reduced(): void
    {
        $this->travelTo('2026-10-05 09:00:00');
        $staff = User::factory()->staff()->create();
        $venue = Venue::factory()->create([
            'capacity' => 100,
            'event_types' => [Venue::EVENT_TYPE_MEETING],
        ]);
        $eventBooking = EventBooking::factory()->for($venue)->create([
            'guest_count' => 80,
            'starts_at' => '2026-10-20 10:00:00',
            'ends_at' => '2026-10-20 12:00:00',
        ]);
        $venue->update(['capacity' => 50]);

        $response = $this->actingAs($staff)->patch(
            route('management.event-reservations.approve', $eventBooking),
        );

        $response->assertSessionHas('error', "The guest count now exceeds this venue's capacity of 50.");
        $this->assertDatabaseHas('event_bookings', [
            'id' => $eventBooking->id,
            'status' => EventBooking::STATUS_PENDING,
        ]);
    }
}
