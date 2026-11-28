<?php

namespace App\Console\Commands;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Jobs\SendBookingZalo;
use App\Models\Booking;
use App\Support\SlotClock;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Nhắc khách qua Zalo trước giờ chụp NGOI_REMINDER_MINUTES phút. Chạy mỗi phút bằng scheduler.
 * Mỗi mã booking chỉ nhắc 1 lần (đánh dấu reminded_at). Khách đặt sát giờ (trong khoảng nhắc)
 * đã có tin xác nhận nên không nhắc thêm.
 */
class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';

    protected $description = 'Gửi tin Zalo nhắc khách trước giờ chụp';

    public function handle(): int
    {
        $minutes = (int) config('ngoi.reminder_minutes');
        if ($minutes <= 0) {
            $this->info('Đã tắt nhắc lịch (NGOI_REMINDER_MINUTES = 0).');

            return self::SUCCESS;
        }

        $now = now();
        $until = $now->copy()->addMinutes($minutes);
        $sent = 0;

        Booking::query()
            ->onDate(SlotClock::today())
            ->where('status', BookingStatus::Booked)
            ->where('source', BookingSource::Online)
            ->whereNull('reminded_at')
            ->where('slot_time', '>', $now->format('H:i:s'))
            ->where('slot_time', '<=', $until->isSameDay($now) ? $until->format('H:i:s') : '23:59:59')
            ->pluck('booking_code')
            ->unique()
            ->each(function (string $code) use ($minutes, &$sent) {
                $rows = Booking::query()->where('booking_code', $code)->where('status', BookingStatus::Booked)->orderBy('slot_time')->get();
                Booking::query()->where('booking_code', $code)->update(['reminded_at' => now()]);

                $first = $rows->first();
                $start = Carbon::parse($first->booking_date->toDateString().' '.$first->time());
                if ($first->created_at->gt($start->copy()->subMinutes($minutes))) {
                    return; // đặt sát giờ: tin xác nhận vừa gửi là đủ
                }
                SendBookingZalo::dispatch('reminder', $rows->modelKeys());
                $sent++;
            });

        $this->info("Đã gửi {$sent} tin nhắc lịch.");

        return self::SUCCESS;
    }
}
