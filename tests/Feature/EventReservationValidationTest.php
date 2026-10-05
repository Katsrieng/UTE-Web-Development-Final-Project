<?php

namespace Tests\Feature;

use App\Models\EventBooking;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EventReservationValidationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_count_cannot_exceed_venue_capacity(): void
    {
        $this->travelTo('2026-10-05 09:00:00');
        $customer = User::factory()->create();
        $venue = Venue::factory()->create([
            'capacity' => 50,
            'event_types' => [Venue::EVENT_TYPE_MEETING],
        ]);

        $response = $this->actingAs($customer)
            ->from(route('event-reservations.create'))
            ->post(route('event-reservations.store'), $this->validPayload($venue, [
                'guest_count' => 51,
            ]));

        $response->assertRedirect(route('event-reservations.create'));
        $response->assertSessionHasErrors([
            'guest_count' => "The guest count may not exceed this venue's capacity of 50.",
        ]);
        $this->assertDatabaseCount('event_bookings', 0);
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        $this->travelTo('2026-10-05 09:00:00');
        $customer = User::factory()->create();
        $venue = Venue::factory()->create([
            'capacity' => 50,
            'event_types' => [Venue::EVENT_TYPE_MEETING],
        ]);

        $response = $this->actingAs($customer)
            ->post(route('event-reservations.store'), $this->validPayload($venue, [
                'start_time' => '14:00',
                'end_time' => '13:00',
            ]));

        $response->assertSessionHasErrors([
            'end_time' => 'The end time must be after the start time.',
        ]);
        $this->assertDatabaseCount('event_bookings', 0);
    }

    #[DataProvider('activeStatuses')]
    public function test_active_reservation_blocks_overlapping_time(string $status): void
    {
        $this->travelTo('2026-10-05 09:00:00');
        $customer = User::factory()->create();
        $venue = Venue::factory()->create([
            'capacity' => 50,
            'event_types' => [Venue::EVENT_TYPE_MEETING],
        ]);
        EventBooking::factory()->for($venue)->create([
            'status' => $status,
            'starts_at' => '2026-10-20 10:00:00',
            'ends_at' => '2026-10-20 12:00:00',
        ]);

        $response = $this->actingAs($customer)
            ->post(route('event-reservations.store'), $this->validPayload($venue, [
                'start_time' => '11:00',
                'end_time' => '13:00',
            ]));

        $response->assertSessionHasErrors([
            'schedule' => 'This venue already has an active reservation during the selected time.',
        ]);
        $this->assertDatabaseCount('event_bookings', 1);
    }

    #[DataProvider('terminalStatuses')]
    public function test_terminal_reservation_releases_its_time_slot(string $status): void
    {
        $this->travelTo('2026-10-05 09:00:00');
        $customer = User::factory()->create();
        $venue = Venue::factory()->create([
            'capacity' => 50,
            'event_types' => [Venue::EVENT_TYPE_MEETING],
        ]);
        EventBooking::factory()->for($venue)->create([
            'status' => $status,
            'starts_at' => '2026-10-20 10:00:00',
            'ends_at' => '2026-10-20 12:00:00',
        ]);

        $response = $this->actingAs($customer)
            ->post(route('event-reservations.store'), $this->validPayload($venue, [
                'start_time' => '11:00',
                'end_time' => '13:00',
            ]));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('event_bookings', 2);
    }

    public static function activeStatuses(): array
    {
        return [
            'pending' => [EventBooking::STATUS_PENDING],
            'approved' => [EventBooking::STATUS_APPROVED],
        ];
    }

    public static function terminalStatuses(): array
    {
        return [
            'rejected' => [EventBooking::STATUS_REJECTED],
            'cancelled' => [EventBooking::STATUS_CANCELLED],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(Venue $venue, array $overrides = []): array
    {
        return array_merge([
            'venue_id' => $venue->id,
            'event_type' => Venue::EVENT_TYPE_MEETING,
            'event_date' => '2026-10-20',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'guest_count' => 20,
            'special_requests' => null,
        ], $overrides);
    }
}
