<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingConfirmed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Gửi email xác nhận booking bất đồng bộ qua queue (khách không phải chờ gửi mail). */
class SendBookingConfirmation implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 60];

    public function __construct(public string $bookingCode, public int $userId) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        $bookings = Booking::query()->with(['room', 'branch'])->where('booking_code', $this->bookingCode)->orderBy('slot_time')->get();
        if ($user && $bookings->isNotEmpty()) {
            $user->notify(new BookingConfirmed($bookings));
        }
    }
}
