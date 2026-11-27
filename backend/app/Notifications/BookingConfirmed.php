<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class BookingConfirmed extends Notification
{
    /** @param Collection<int, Booking> $bookings */
    public function __construct(public Collection $bookings) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $first = $this->bookings->first();
        $mail = (new MailMessage)
            ->subject("Ngói Photobooth – Xác nhận lịch chụp {$first->booking_code}")
            ->greeting("Chào {$first->customer_name},")
            ->line("Ngói đã nhận lịch chụp của bạn tại {$first->branch->name} ngày {$first->booking_date->format('d/m/Y')}:");
        foreach ($this->bookings as $b) {
            $mail->line("• {$b->time()} – {$b->room->label()} – ".number_format($b->price, 0, ',', '.').'đ');
        }

        return $mail->line("Mã booking: **{$first->booking_code}**. Vui lòng đến đúng giờ, quá giờ lịch có thể bị hủy.")
            ->action('Xem lịch của tôi', route('my-bookings'))
            ->salutation('Hẹn gặp bạn tại Ngói!');
    }
}
