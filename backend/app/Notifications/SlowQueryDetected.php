<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class SlowQueryDetected extends Notification
{
    /** @param  string  $kind  'query' (1 câu SQL chậm) hoặc 'request' (tổng thời gian query của 1 request quá lâu) */
    public function __construct(public string $kind, public string $sql, public float $ms, public ?string $url = null) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function summary(): string
    {
        $what = $this->kind === 'request' ? 'Request có tổng thời gian truy vấn' : 'Truy vấn chậm';

        return "[Ngói] {$what} {$this->ms} ms".($this->url ? " tại {$this->url}" : '').': '.Str::limit($this->sql, 300);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[Ngói] Cảnh báo truy vấn chậm: '.round($this->ms).' ms')
            ->line($this->kind === 'request'
                ? 'Tổng thời gian truy vấn database của một request vượt ngưỡng 2 giây.'
                : 'Một câu truy vấn database chậm hơn ngưỡng '.config('ngoi.slow_query_ms').' ms.')
            ->line('Thời gian: '.$this->ms.' ms')
            ->line('Đường dẫn: '.($this->url ?? '(không có, chạy từ console/queue)'))
            ->line('SQL: '.Str::limit($this->sql, 1000))
            ->line('Chi tiết đầy đủ trong storage/logs/slow-query-*.log và Telescope. Cùng một câu SQL chỉ báo lại sau '.config('ngoi.slow_query_alert.throttle_minutes').' phút.');
    }
}
