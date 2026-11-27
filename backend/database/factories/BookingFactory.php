<?php

namespace Database\Factories;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Booking> */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_code' => 'NG'.strtoupper(fake()->unique()->bothify('??####')),
            'room_id' => Room::factory(),
            'branch_id' => fn (array $a) => Room::find($a['room_id'])->branch_id,
            'price' => fn (array $a) => Room::find($a['room_id'])->price_per_slot,
            'booking_date' => now()->toDateString(),
            'slot_time' => '15:00:00',
            'customer_name' => fake()->name(),
            'customer_phone' => '09'.fake()->numerify('########'),
            'source' => BookingSource::Online,
            'status' => BookingStatus::Booked,
        ];
    }

    public function at(string $time, ?string $date = null): static
    {
        return $this->state(fn () => array_filter(['slot_time' => $time.':00', 'booking_date' => $date]));
    }

    public function status(BookingStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'checked_in_at' => in_array($status, [BookingStatus::CheckedIn, BookingStatus::Completed]) ? now() : null,
            'completed_at' => $status === BookingStatus::Completed ? now() : null,
            'cancelled_at' => $status === BookingStatus::Cancelled ? now() : null,
            'cancel_reason' => $status === BookingStatus::Cancelled ? 'Khách đến trễ' : '',
        ]);
    }
}
