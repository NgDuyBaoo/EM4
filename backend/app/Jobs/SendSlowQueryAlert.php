<?php

namespace App\Jobs;

use App\Monitoring\AlertChannels;
use App\Notifications\SlowQueryDetected;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Gửi cảnh báo truy vấn chậm qua email (chủ tiệm hoặc NGOI_ALERT_MAIL) và Slack webhook nếu có. */
class SendSlowQueryAlert implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public string $kind, public string $sql, public float $ms, public ?string $url = null) {}

    public function handle(): void
    {
        $notification = new SlowQueryDetected($this->kind, $this->sql, $this->ms, $this->url);
        AlertChannels::send($notification, $notification->summary());
    }
}
