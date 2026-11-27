<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Branch;
use App\Support\SlotClock;
use Illuminate\Support\Facades\Cache;

/**
 * Lịch trống/bận của 1 chi nhánh trong 1 ngày.
 * Phần nặng (phòng + slot đã đặt) được cache 60 giây và xóa ngay khi có booking thay đổi (BookingObserver).
 * Trạng thái "đã qua" luôn tính lại theo giờ hiện tại, không cache.
 */
class AvailabilityService
{
    public static function cacheKey(int $branchId, string $date): string
    {
        return "availability:{$branchId}:{$date}";
    }

    public function forBranch(Branch $branch, ?string $date = null): array
    {
        $date ??= SlotClock::today();

        $base = Cache::remember(self::cacheKey($branch->id, $date), 60, function () use ($branch, $date) {
            $rooms = $branch->activeRooms()->get(['id', 'branch_id', 'name', 'style', 'description', 'price_per_slot']);
            $taken = Booking::query()->active()->onDate($date)->where('branch_id', $branch->id)
                ->get(['room_id', 'slot_time'])
                ->map(fn ($b) => $b->room_id.'|'.$b->time())
                ->flip()->all();

            return ['rooms' => $rooms->toArray(), 'taken' => $taken];
        });

        $slots = SlotClock::slots($branch->openAt(), $branch->closeAt());
        $isToday = $date === SlotClock::today();

        return [
            'date' => $date,
            'now' => $isToday ? now()->format('H:i') : null,
            'slots' => $slots,
            'rooms' => array_map(fn (array $room) => $room + [
                'slots' => array_map(fn (string $t) => [
                    'time' => $t,
                    'state' => isset($base['taken'][$room['id'].'|'.$t]) ? 'booked'
                        : (SlotClock::started($t, $date) ? 'past' : 'free'),
                ], $slots),
            ], $base['rooms']),
        ];
    }
}
