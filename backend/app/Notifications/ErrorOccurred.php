<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ErrorOccurred extends Notification
{
    public function __construct(
        public string $exception,
        public string $message,
        public string $file,
        public int $line,
        public string $where,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function summary(): string
    {
        return '[Ngói] Lỗi 500 tại '.$this->where.': '.class_basename($this->exception).' – '.Str::limit($this->message, 200)." ({$this->file}:{$this->line})";
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('[Ngói] Lỗi hệ thống: '.class_basename($this->exception))
            ->line('Hệ thống vừa gặp lỗi chưa được xử lý (khách nhận trang lỗi 500).')
            ->line('Nơi xảy ra: '.$this->where)
            ->line('Lỗi: '.$this->exception)
            ->line('Thông điệp: '.$this->message)
            ->line("Vị trí: {$this->file}:{$this->line}")
            ->line('Xem stack trace đầy đủ trong storage/logs/laravel-*.log và Telescope. Cùng lỗi này chỉ báo lại sau '.config('ngoi.error_alert.throttle_minutes').' phút.');
    }
}
