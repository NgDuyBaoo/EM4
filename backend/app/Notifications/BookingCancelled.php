<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Support\Str;

/** Tin Zalo báo khách lịch chụp đã bị hủy (nhân viên hủy hoặc hệ thống tự hủy vì quá giờ). */
class BookingCancelled extends BookingZaloNotification
{
    protected string $template = 'cancelled';

    protected function params(Booking $first): array
    {
        return $this->common($first) + ['cancel_reason' => Str::limit($first->cancel_reason ?: 'Lịch đã bị hủy', 100, '')];
    }
}
