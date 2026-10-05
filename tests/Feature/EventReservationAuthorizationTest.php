<?php

namespace Tests\Feature;

use App\Models\EventBooking;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EventReservationAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_from_customer_reservations(): void
    {
        $this->get(route('event-reservations.index'))->assertRedirect(route('login'));
        $this->get(route('event-reservations.create'))->assertRedirect(route('login'));
    }

    public function test_customer_cannot_learn_about_another_customers_reservation(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($otherCustomer)->create();

        $this->actingAs($customer)
            ->get(route('event-reservations.show', $eventBooking))
            ->assertNotFound();

        $this->actingAs($customer)
            ->patch(route('event-reservations.cancel', $eventBooking))
            ->assertNotFound();
    }

    public function test_customer_cannot_access_event_management(): void
    {
        $customer = User::factory()->create();
        $venue = Venue::factory()->create();

        $this->actingAs($customer)
            ->get(route('management.venues.index'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('management.venues.edit', $venue))
            ->assertForbidden();
    }

    public function test_staff_can_view_all_event_reservations(): void
    {
        $staff = User::factory()->staff()->create();
        $eventBooking = EventBooking::factory()->create();

        $this->actingAs($staff)
            ->get(route('management.event-reservations.show', $eventBooking))
            ->assertSee($eventBooking->venue->name);
    }

    public function test_archived_venue_is_not_visible_on_public_detail_page(): void
    {
        $venue = Venue::factory()->archived()->create();

        $this->get(route('venues.show', $venue))->assertNotFound();
    }
}
