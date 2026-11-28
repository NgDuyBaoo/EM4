<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingReminder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/** Gửi tin Zalo ZNS cho khách qua queue (khách không phải chờ gọi API Zalo). Lỗi mạng/Zalo thì thử lại. */
class SendBookingZalo implements ShouldQueue
{
    use Queueable;

    public const KINDS = [
        'confirmed' => BookingConfirmed::class,
        'reminder' => BookingReminder::class,
        'cancelled' => BookingCancelled::class,
    ];

    public int $tries = 3;

    public array $backoff = [10, 60];

    /** @param list<int> $bookingIds các slot cùng 1 mã booking */
    public function __construct(public string $kind, public array $bookingIds) {}

    public function handle(): void
    {
        $bookings = Booking::query()->with(['room', 'branch'])->whereKey($this->bookingIds)->orderBy('slot_time')->get();
        $class = self::KINDS[$this->kind] ?? null;
        if (! $class || $bookings->isEmpty()) {
            return;
        }

        Notification::route('zalo', $bookings->first()->customer_phone)->notifyNow(new $class($bookings));
    }

    public function failed(Throwable $e): void
    {
        Log::channel('zalo')->error('Không gửi được tin Zalo cho khách', ['kind' => $this->kind, 'bookings' => $this->bookingIds, 'error' => $e->getMessage()]);
    }
}
