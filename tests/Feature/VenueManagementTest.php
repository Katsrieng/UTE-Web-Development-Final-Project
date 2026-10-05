<?php

namespace Tests\Feature;

use App\Models\EventBooking;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class VenueManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_staff_can_create_a_venue(): void
    {
        $staff = User::factory()->staff()->create();

        $response = $this->actingAs($staff)->post(route('management.venues.store'), [
            'name' => 'Lotus Ballroom',
            'description' => 'A ballroom overlooking the garden.',
            'location' => 'Ground Floor',
            'capacity' => 180,
            'price' => 1800,
            'event_types' => [Venue::EVENT_TYPE_WEDDING, Venue::EVENT_TYPE_PARTY],
        ]);

        $venue = Venue::query()->sole();

        $response->assertRedirect(route('management.venues.show', $venue));
        $this->assertDatabaseHas('venues', [
            'id' => $venue->id,
            'name' => 'Lotus Ballroom',
            'capacity' => 180,
            'is_active' => true,
        ]);
    }

    public function test_archiving_venue_preserves_reservation_history(): void
    {
        $staff = User::factory()->staff()->create();
        $venue = Venue::factory()->create();
        $eventBooking = EventBooking::factory()->for($venue)->create();

        $response = $this->actingAs($staff)->delete(route('management.venues.destroy', $venue));

        $response->assertRedirect(route('management.venues.index'));
        $this->assertDatabaseHas('venues', [
            'id' => $venue->id,
            'is_active' => false,
        ]);
        $this->assertModelExists($eventBooking);
    }
}
