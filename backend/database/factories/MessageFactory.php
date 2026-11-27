<?php

namespace Database\Factories;

use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Message> */
class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'contact' => '09'.fake()->numerify('########'),
            'subject' => fake()->randomElement(['Góp ý dịch vụ', 'Hỏi về lịch đã đặt', 'Nhắn tin riêng']),
            'body' => fake()->paragraph(),
        ];
    }
}
