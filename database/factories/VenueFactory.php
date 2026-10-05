<?php

namespace Database\Factories;

use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Venue>
 */
class VenueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' Event Space',
            'description' => fake()->paragraph(),
            'location' => fake()->randomElement(['Ground Floor', 'Lobby Level', 'Rooftop', 'Garden Wing']),
            'capacity' => fake()->numberBetween(20, 500),
            'price' => fake()->randomFloat(2, 100, 5000),
            'event_types' => fake()->randomElements(
                Venue::EVENT_TYPES,
                fake()->numberBetween(1, count(Venue::EVENT_TYPES)),
            ),
            'images' => [],
            'is_active' => true,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }
}
