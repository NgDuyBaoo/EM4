<?php

namespace App\Notifications;

use App\Models\Booking;

/** Tin Zalo xác nhận lịch chụp ngay sau khi khách đặt online. */
class BookingConfirmed extends BookingZaloNotification
{
    protected string $template = 'confirmed';

    protected function params(Booking $first): array
    {
        return $this->common($first) + ['total' => (string) $this->bookings->sum('price')];
    }
}
