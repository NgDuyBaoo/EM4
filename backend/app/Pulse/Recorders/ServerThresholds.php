<?php

namespace App\Pulse\Recorders;

use App\Jobs\SendServerAlert;
use App\Support\ServerStats;
use Illuminate\Support\Facades\Cache;
use Laravel\Pulse\Events\SharedBeat;
use Laravel\Pulse\Recorders\Concerns\Throttling;

/**
 * Cảnh báo theo ngưỡng: mỗi phút kiểm CPU, RAM, đĩa; vượt ngưỡng (config ngoi.server_alert) thì gửi email/Slack.
 * Cùng một chỉ số chỉ báo 1 lần trong throttle_minutes phút.
 */
class ServerThresholds
{
    use Throttling;

    public string $listen = SharedBeat::class;

    public function __construct(protected ServerStats $stats) {}

    public function record(SharedBeat $event): void
    {
        $this->throttle(60, $event, fn () => $this->check());
    }

    /** @return list<string> các chỉ số vượt ngưỡng */
    public function check(): array
    {
        $cfg = config('ngoi.server_alert');
        if (! $cfg['enabled']) {
            return [];
        }

        $values = [
            'cpu' => $this->stats->cpuPercent(),
            'memory' => $this->stats->memoryPercent(),
            'disk' => $this->stats->diskPercent($cfg['disk_directory']),
        ];

        $exceeded = [];
        foreach ($values as $metric => $value) {
            $limit = (float) $cfg['thresholds'][$metric];
            if ($value === null || $value < $limit) {
                continue;
            }
            $exceeded[] = $metric;
            if (Cache::add("server-alert:{$metric}", true, now()->addMinutes((int) $cfg['throttle_minutes']))) {
                SendServerAlert::dispatch($metric, $value, $limit, gethostname() ?: 'server');
            }
        }

        return $exceeded;
    }
}
