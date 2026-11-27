<?php

namespace Database\Seeders;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Support\SlotClock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Booking mẫu cho 30 ngày qua (để có số liệu thống kê) và hôm nay (để thấy lưới lịch).
 * Không tạo trùng slot nhờ đánh dấu các slot đã dùng.
 */
class BookingSeeder extends Seeder
{
    public function run(): void
    {
        if (Booking::exists()) {
            return;
        }
        $rooms = Room::with('branch')->get();
        $customers = User::where('role', 'customer')->get();
        $slots = SlotClock::slots();
        $now = SlotClock::nowMinutes();
        $rows = [];

        for ($d = 30; $d >= 0; $d--) {
            $date = now()->subDays($d)->toDateString();
            foreach ($rooms as $room) {
                $picked = collect($slots)->random(fake()->numberBetween(6, 22));
                foreach ($picked as $time) {
                    $isPast = $d > 0 || SlotClock::minutes($time) + 10 <= $now;
                    $status = $isPast
                        ? fake()->randomElement([BookingStatus::Completed, BookingStatus::Completed, BookingStatus::Completed, BookingStatus::Completed, BookingStatus::Cancelled])
                        : BookingStatus::Booked;
                    $customer = fake()->boolean(70) ? $customers->random() : null;
                    $rows[] = [
                        'booking_code' => 'NG'.strtoupper(Str::random(6)),
                        'user_id' => $customer?->id,
                        'branch_id' => $room->branch_id,
                        'room_id' => $room->id,
                        'booking_date' => $date,
                        'slot_time' => $time.':00',
                        'customer_name' => $customer?->name ?? fake()->name(),
                        'customer_phone' => $customer?->phone ?? '09'.fake()->numerify('########'),
                        'source' => $customer ? BookingSource::Online->value : BookingSource::Walkin->value,
                        'status' => $status->value,
                        'price' => $room->price_per_slot,
                        'note' => '',
                        'cancel_reason' => $status === BookingStatus::Cancelled ? 'Khách đến trễ' : '',
                        'cancelled_at' => $status === BookingStatus::Cancelled ? now() : null,
                        'completed_at' => $status === BookingStatus::Completed ? now() : null,
                        'checked_in_at' => in_array($status, [BookingStatus::Completed, BookingStatus::CheckedIn]) ? now() : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        // Chèn theo lô 500 dòng thay vì từng dòng (nhanh hơn nhiều)
        foreach (array_chunk($rows, 500) as $chunk) {
            Booking::insert($chunk);
        }
    }
}
