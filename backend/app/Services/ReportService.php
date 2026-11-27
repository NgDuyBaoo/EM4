<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Room;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Thống kê lượt đặt, doanh thu, hiệu suất phòng. Kết quả cache 5 phút. */
class ReportService
{
    public function summary(string $from, string $to, ?int $branchId = null): array
    {
        $key = "report:{$from}:{$to}:".($branchId ?? 'all');

        return Cache::remember($key, 300, fn () => $this->build($from, $to, $branchId));
    }

    private function build(string $from, string $to, ?int $branchId): array
    {
        $cancelled = BookingStatus::Cancelled->value;
        $completed = BookingStatus::Completed->value;

        // 1 truy vấn GROUP BY thay vì đếm từng ngày (tránh N+1)
        $daily = Booking::query()
            ->selectRaw('booking_date as d')
            ->selectRaw("SUM(CASE WHEN status <> '{$cancelled}' THEN 1 ELSE 0 END) as bookings")
            ->selectRaw("SUM(CASE WHEN status = '{$completed}' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN status = '{$cancelled}' THEN 1 ELSE 0 END) as cancelled")
            ->selectRaw("SUM(CASE WHEN status = '{$completed}' THEN price ELSE 0 END) as revenue")
            ->whereBetween('booking_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->groupBy('booking_date')
            ->get()
            ->keyBy(fn ($r) => substr((string) $r->d, 0, 10));

        $days = [];
        foreach (CarbonPeriod::create($from, $to) as $day) {
            $r = $daily->get($day->toDateString());
            $days[] = [
                'date' => $day->toDateString(),
                'bookings' => (int) ($r->bookings ?? 0),
                'completed' => (int) ($r->completed ?? 0),
                'cancelled' => (int) ($r->cancelled ?? 0),
                'revenue' => (int) ($r->revenue ?? 0),
            ];
        }
        $dayCount = count($days);

        $rooms = Room::query()
            ->with('branch:id,name,open_time,close_time')
            ->where('is_active', true)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->withCount(['bookings as booked' => fn ($q) => $q->active()->whereBetween('booking_date', [$from, $to])])
            ->withSum(['bookings as revenue' => fn ($q) => $q->where('status', $completed)->whereBetween('booking_date', [$from, $to])], 'price')
            ->orderBy('branch_id')->orderBy('sort_order')
            ->get()
            ->map(function (Room $room) use ($dayCount) {
                $perDay = count(\App\Support\SlotClock::slots($room->branch->openAt(), $room->branch->closeAt()));
                $capacity = $perDay * $dayCount;

                return [
                    'room_id' => $room->id,
                    'room' => $room->label(),
                    'branch' => $room->branch->name,
                    'bookings' => (int) $room->booked,
                    'revenue' => (int) $room->revenue,
                    'occupancy' => $capacity ? round($room->booked / $capacity, 4) : 0,
                ];
            });

        $branches = Branch::query()
            ->when($branchId, fn ($q) => $q->whereKey($branchId))
            ->withCount(['bookings as booked' => fn ($q) => $q->active()->whereBetween('booking_date', [$from, $to])])
            ->withCount(['bookings as cancelled' => fn ($q) => $q->where('status', $cancelled)->whereBetween('booking_date', [$from, $to])])
            ->withSum(['bookings as revenue' => fn ($q) => $q->where('status', $completed)->whereBetween('booking_date', [$from, $to])], 'price')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Branch $b) => ['branch_id' => $b->id, 'branch' => $b->name, 'bookings' => (int) $b->booked, 'cancelled' => (int) $b->cancelled, 'revenue' => (int) $b->revenue]);

        return [
            'from' => $from,
            'to' => $to,
            'totals' => [
                'bookings' => array_sum(array_column($days, 'bookings')),
                'completed' => array_sum(array_column($days, 'completed')),
                'cancelled' => array_sum(array_column($days, 'cancelled')),
                'revenue' => array_sum(array_column($days, 'revenue')),
            ],
            'days' => $days,
            'rooms' => $rooms->values()->all(),
            'branches' => $branches->values()->all(),
        ];
    }
}
