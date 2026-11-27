<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Các phép tính giờ theo giờ Việt Nam (app.timezone = Asia/Ho_Chi_Minh).
 * Dùng now() của Laravel nên test có thể cố định thời gian bằng $this->travelTo().
 */
class SlotClock
{
    public static function today(): string
    {
        return now()->toDateString();
    }

    public static function minutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', $time));

        return $h * 60 + $m;
    }

    public static function time(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    public static function nowMinutes(): int
    {
        $n = now();

        return $n->hour * 60 + $n->minute;
    }

    /** @return list<string> 08:30–22:30 → 08:30, 08:40, …, 22:20 (84 slot) */
    public static function slots(string $open = '08:30', string $close = '22:30'): array
    {
        $step = (int) config('ngoi.slot_minutes', 10);
        $out = [];
        for ($m = self::minutes($open); $m + $step <= self::minutes($close); $m += $step) {
            $out[] = self::time($m);
        }

        return $out;
    }

    public static function isValidSlot(string $time, string $open, string $close): bool
    {
        return (bool) preg_match('/^\d{2}:\d{2}$/', $time) && in_array($time, self::slots($open, $close), true);
    }

    /** Slot đã bắt đầu (khách online không đặt/tự hủy được nữa) */
    public static function started(string $time, ?string $date = null): bool
    {
        $date ??= self::today();
        if ($date !== self::today()) {
            return $date < self::today();
        }

        return self::minutes($time) <= self::nowMinutes();
    }

    /** Slot đã kết thúc hẳn */
    public static function ended(string $time, ?string $date = null): bool
    {
        $date ??= self::today();
        if ($date !== self::today()) {
            return $date < self::today();
        }

        return self::minutes($time) + (int) config('ngoi.slot_minutes', 10) <= self::nowMinutes();
    }

    public static function endOfDay(): Carbon
    {
        return now()->endOfDay();
    }
}
