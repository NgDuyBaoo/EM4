<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Room> */
class RoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => 'Phòng '.fake()->numberBetween(1, 9),
            'style' => fake()->randomElement(['Vintage', 'Classic', 'Film', 'Modern']),
            'description' => fake()->sentence(),
            'price_per_slot' => 50000,
            'is_active' => true,
            'sort_order' => 1,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
