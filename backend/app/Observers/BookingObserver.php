<?php

namespace App\Observers;

use App\Models\Booking;
use App\Services\AvailabilityService;
use Illuminate\Support\Facades\Cache;

/** Xóa cache lịch trống của chi nhánh/ngày khi booking thay đổi, để khách luôn thấy dữ liệu đúng. */
class BookingObserver
{
    public function saved(Booking $booking): void
    {
        $this->flush($booking);
    }

    public function deleted(Booking $booking): void
    {
        $this->flush($booking);
    }

    private function flush(Booking $booking): void
    {
        Cache::forget(AvailabilityService::cacheKey($booking->branch_id, $booking->booking_date->toDateString()));
    }
}
