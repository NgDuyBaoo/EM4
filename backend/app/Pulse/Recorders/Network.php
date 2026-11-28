<?php

namespace App\Pulse\Recorders;

use App\Support\ServerStats;
use Illuminate\Support\Str;
use Laravel\Pulse\Events\SharedBeat;
use Laravel\Pulse\Pulse;
use Laravel\Pulse\Recorders\Concerns\Throttling;
use Laravel\Pulse\Recorders\Servers;

/**
 * Recorder Pulse tự viết: lưu lượng mạng vào/ra (byte/giây) của máy chủ.
 * Chạy trong `php artisan pulse:check` (container pulse), 15 giây một lần, hiện ở thẻ "Mạng" trên /pulse.
 */
class Network
{
    use Throttling;

    public string $listen = SharedBeat::class;

    /** @var array{rx:int, tx:int, at:int}|null */
    protected ?array $previous = null;

    public function __construct(protected Pulse $pulse, protected ServerStats $stats) {}

    public function record(SharedBeat $event): void
    {
        $this->throttle(15, $event, function (SharedBeat $event) {
            $now = $this->stats->network();
            if ($now === null) {
                return;
            }
            $at = $event->time->getTimestamp();
            $prev = $this->previous;
            $this->previous = $now + ['at' => $at];
            if ($prev === null || $at <= $prev['at'] || $now['rx'] < $prev['rx']) {
                return; // lần đầu hoặc bộ đếm vừa reset: chưa tính được tốc độ
            }

            $seconds = $at - $prev['at'];
            $rx = (int) round(($now['rx'] - $prev['rx']) / $seconds);
            $tx = (int) round(($now['tx'] - $prev['tx']) / $seconds);
            $server = (string) config('pulse.recorders.'.Servers::class.'.server_name');
            $slug = Str::slug($server);

            $this->pulse->record('network_rx', $slug, $rx, $event->time)->avg()->max()->onlyBuckets();
            $this->pulse->record('network_tx', $slug, $tx, $event->time)->avg()->max()->onlyBuckets();
            $this->pulse->set('network', $slug, json_encode(['name' => $server, 'rx' => $rx, 'tx' => $tx], JSON_THROW_ON_ERROR), $event->time);
        });
    }
}
