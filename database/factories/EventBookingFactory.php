<?php

namespace Database\Factories;

use App\Models\EventBooking;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventBooking>
 */
class EventBookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+1 day', '+3 months');
        $endsAt = (clone $startsAt)->modify('+'.fake()->numberBetween(1, 8).' hours');

        return [
            'user_id' => User::factory(),
            'venue_id' => Venue::factory()->state([
                'event_types' => [Venue::EVENT_TYPE_MEETING],
            ]),
            'event_type' => Venue::EVENT_TYPE_MEETING,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'guest_count' => fake()->numberBetween(5, 20),
            'special_requests' => fake()->optional()->sentence(),
            'quoted_price' => fake()->randomFloat(2, 100, 5000),
            'status' => EventBooking::STATUS_PENDING,
            'status_note' => null,
            'processed_by' => null,
            'processed_at' => null,
            'cancelled_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EventBooking::STATUS_APPROVED,
            'processed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EventBooking::STATUS_REJECTED,
            'status_note' => 'The requested time is unavailable.',
            'processed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EventBooking::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'processed_at' => now(),
        ]);
    }
}
