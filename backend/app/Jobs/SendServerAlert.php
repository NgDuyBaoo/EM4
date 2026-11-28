<?php

namespace App\Jobs;

use App\Monitoring\AlertChannels;
use App\Notifications\ServerThresholdExceeded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Gửi cảnh báo máy chủ vượt ngưỡng qua email và Slack. */
class SendServerAlert implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public string $metric, public float $value, public float $limit, public string $server) {}

    public function handle(): void
    {
        $n = new ServerThresholdExceeded($this->metric, $this->value, $this->limit, $this->server);
        AlertChannels::send($n, $n->summary());
    }
}
