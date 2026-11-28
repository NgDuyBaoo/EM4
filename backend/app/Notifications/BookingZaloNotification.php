<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Notifications\Channels\ZaloChannel;
use App\Notifications\Messages\ZaloMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Tin Zalo ZNS gửi khách cho một mã booking (nhiều slot chung 1 mã).
 * Tên tham số trong params() phải trùng tên tham số khi đăng ký mẫu ZNS (docs/integrations/zalo-zns.md).
 */
abstract class BookingZaloNotification extends Notification
{
    /** Khóa mẫu trong config services.zalo.templates. */
    protected string $template;

    /** @param Collection<int, Booking> $bookings các slot cùng mã, đã nạp room và branch */
    public function __construct(public Collection $bookings) {}

    public function via(object $notifiable): array
    {
        return [ZaloChannel::class];
    }

    public function toZalo(object $notifiable): ZaloMessage
    {
        $first = $this->bookings->sortBy('slot_time')->first();

        return new ZaloMessage($this->template, $this->params($first), $first->booking_code.'-'.$this->template.'-'.now()->format('His'));
    }

    /** @return array<string, string> */
    abstract protected function params(Booking $first): array;

    /** Tham số dùng chung. Zalo giới hạn độ dài: tên khách 30 ký tự, chuỗi khác cắt ở 100. */
    protected function common(Booking $first): array
    {
        return [
            'customer_name' => Str::limit($first->customer_name, 30, ''),
            'booking_code' => $first->booking_code,
            'branch_name' => Str::limit($first->branch->name, 100, ''),
            'booking_date' => $first->booking_date->format('d/m/Y'),
            'slot_time' => $first->time(),
            'slots' => Str::limit($this->bookings->sortBy('slot_time')->map(fn (Booking $b) => $b->time().' '.$b->room->label())->implode(', '), 100),
        ];
    }
}
