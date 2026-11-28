<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServerThresholdExceeded extends Notification
{
    public const LABELS = ['cpu' => 'CPU', 'memory' => 'RAM', 'disk' => 'Ổ đĩa'];

    public function __construct(public string $metric, public float $value, public float $limit, public string $server) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function summary(): string
    {
        $label = self::LABELS[$this->metric] ?? $this->metric;

        return "[Ngói] Máy chủ {$this->server}: {$label} đang {$this->value}% (ngưỡng {$this->limit}%)";
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = self::LABELS[$this->metric] ?? $this->metric;

        return (new MailMessage)
            ->error()
            ->subject("[Ngói] Cảnh báo máy chủ: {$label} {$this->value}%")
            ->line("Máy chủ {$this->server} có {$label} đang dùng {$this->value}%, vượt ngưỡng {$this->limit}%.")
            ->line('Xem biểu đồ CPU, RAM, đĩa và mạng tại /pulse. Chỉ số này chỉ báo lại sau '.config('ngoi.server_alert.throttle_minutes').' phút.');
    }
}
