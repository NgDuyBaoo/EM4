<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Branch> */
class BranchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'b'.fake()->unique()->numberBetween(10, 9999),
            'name' => fake()->numberBetween(1, 200).' '.fake()->streetName(),
            'address' => fake()->address(),
            'phone' => '0234'.fake()->numerify('######'),
            'open_time' => '08:30:00',
            'close_time' => '22:30:00',
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
