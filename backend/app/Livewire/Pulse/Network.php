<?php

namespace App\Livewire\Pulse;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;

/** Thẻ "Mạng" trên dashboard /pulse: tốc độ nhận/gửi hiện tại, trung bình và cao nhất trong khoảng thời gian chọn. */
#[Lazy]
class Network extends Card
{
    public function render(): Renderable
    {
        [$servers, $time, $runAt] = $this->remember(function () {
            $avg = $this->graph(['network_rx', 'network_tx'], 'avg');
            $max = $this->graph(['network_rx', 'network_tx'], 'max');

            return $this->values('network')->map(function ($row, $slug) use ($avg, $max) {
                $v = json_decode($row->value, true);
                $series = fn ($graphs, $type) => collect($graphs->get($slug)?->get($type) ?? [])->filter(fn ($x) => $x !== null);

                return (object) [
                    'name' => $v['name'],
                    'rx' => $v['rx'],
                    'tx' => $v['tx'],
                    'rx_avg' => (int) $series($avg, 'network_rx')->avg(),
                    'tx_avg' => (int) $series($avg, 'network_tx')->avg(),
                    'rx_max' => (int) $series($max, 'network_rx')->max(),
                    'tx_max' => (int) $series($max, 'network_tx')->max(),
                ];
            })->values();
        });

        return View::make('pulse.network', ['servers' => $servers, 'time' => $time, 'runAt' => $runAt]);
    }

    public static function human(int $bytesPerSecond): string
    {
        $units = ['B/s', 'KB/s', 'MB/s', 'GB/s'];
        $i = 0;
        $v = (float) $bytesPerSecond;
        while ($v >= 1024 && $i < 3) {
            $v /= 1024;
            $i++;
        }

        return round($v, $i ? 1 : 0).' '.$units[$i];
    }
}
