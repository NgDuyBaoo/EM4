<?php

namespace App\Notifications;

use App\Models\Booking;

/** Tin Zalo nhắc khách trước giờ chụp (lệnh bookings:send-reminders). */
class BookingReminder extends BookingZaloNotification
{
    protected string $template = 'reminder';

    protected function params(Booking $first): array
    {
        return $this->common($first);
    }
}
