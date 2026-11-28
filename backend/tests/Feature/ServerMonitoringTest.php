<?php

use App\Jobs\SendServerAlert;
use App\Livewire\Pulse\Network as NetworkCard;
use App\Notifications\ServerThresholdExceeded;
use App\Pulse\Recorders\Network;
use App\Pulse\Recorders\ServerThresholds;
use App\Support\ServerStats;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Pulse\Entry;
use Laravel\Pulse\Events\SharedBeat;
use Laravel\Pulse\Pulse;

/** Số liệu máy chủ giả để test không phụ thuộc máy chạy test. */
function fakeStats(array $values): ServerStats
{
    return new class($values) extends ServerStats
    {
        public function __construct(public array $v) {}

        public function network(): ?array
        {
            return array_shift($this->v['network']);
        }

        public function cpuPercent(): ?float
        {
            return $this->v['cpu'] ?? null;
        }

        public function memoryPercent(): ?float
        {
            return $this->v['memory'] ?? null;
        }

        public function diskPercent(string $directory = '/'): ?float
        {
            return $this->v['disk'] ?? null;
        }
    };
}

it('đọc được /proc/net/dev và số liệu CPU, RAM, đĩa trên Linux', function () {
    $stats = new ServerStats;

    expect($stats->network())->toHaveKeys(['rx', 'tx'])
        ->and($stats->diskPercent('/'))->toBeBetween(0, 100)
        ->and($stats->memoryPercent())->toBeBetween(0, 100);
})->skip(PHP_OS_FAMILY !== 'Linux', 'Chỉ chạy trên Linux');

it('recorder mạng tính tốc độ nhận/gửi theo byte/giây và ghi vào Pulse', function () {
    $pulse = Mockery::spy(Pulse::class);
    $recorded = [];
    $pulse->shouldReceive('record')->andReturnUsing(function ($type, $key, $value, $time) use (&$recorded) {
        return $recorded[$type] = new Entry($time->getTimestamp(), $type, $key, $value);
    });
    $recorder = new Network($pulse, fakeStats(['network' => [['rx' => 1000, 'tx' => 500], ['rx' => 31000, 'tx' => 15500]]]));

    $t = CarbonImmutable::parse('2026-11-28 10:00:00');
    $this->travelTo($t);
    $recorder->record(new SharedBeat($t, 'x'));
    $this->travelTo($t->addSeconds(15));
    $recorder->record(new SharedBeat($t->addSeconds(15), 'x'));

    expect($recorded['network_rx']->value)->toBe(2000)   // (31000 - 1000) / 15 giây
        ->and($recorded['network_tx']->value)->toBe(1000)
        ->and($recorded['network_rx']->aggregations())->toBe(['avg', 'max']);
    $pulse->shouldHaveReceived('set')->withArgs(fn ($type, $key, $value) => $type === 'network' && json_decode($value, true)['rx'] === 2000);
});

it('vượt ngưỡng CPU/RAM/đĩa thì gửi cảnh báo, chưa vượt thì không', function () {
    Queue::fake();
    config(['ngoi.server_alert.thresholds' => ['cpu' => 85, 'memory' => 90, 'disk' => 90]]);

    $exceeded = (new ServerThresholds(fakeStats(['cpu' => 97.5, 'memory' => 40, 'disk' => 93])))->check();

    expect($exceeded)->toBe(['cpu', 'disk']);
    Queue::assertPushed(SendServerAlert::class, 2);
    Queue::assertPushed(SendServerAlert::class, fn ($job) => $job->metric === 'cpu' && $job->value === 97.5 && $job->limit === 85.0);
});

it('cùng một chỉ số chỉ báo một lần trong khoảng chống spam', function () {
    Queue::fake();
    $check = new ServerThresholds(fakeStats(['cpu' => 99]));

    $check->check();
    $check->check();

    Queue::assertPushed(SendServerAlert::class, 1);
});

it('tắt cảnh báo bằng cấu hình', function () {
    Queue::fake();
    config(['ngoi.server_alert.enabled' => false]);

    expect((new ServerThresholds(fakeStats(['cpu' => 99])))->check())->toBe([]);
    Queue::assertNothingPushed();
});

it('job gửi email cảnh báo máy chủ cho chủ cửa hàng', function () {
    Notification::fake();
    $owner = owner();

    (new SendServerAlert('disk', 93.2, 90, 'ngoi-app'))->handle();

    Notification::assertSentTo($owner, ServerThresholdExceeded::class,
        fn ($n) => $n->toMail($owner)->subject === '[Ngói] Cảnh báo máy chủ: Ổ đĩa 93.2%');
});

it('đổi tốc độ mạng sang đơn vị dễ đọc', function () {
    expect(NetworkCard::human(512))->toBe('512 B/s')
        ->and(NetworkCard::human(2048))->toBe('2 KB/s')
        ->and(NetworkCard::human(5 * 1024 * 1024 + 300000))->toBe('5.3 MB/s');
});

it('dashboard /pulse có thẻ Mạng cho chủ cửa hàng', function () {
    $this->actingAs(owner())->get('/pulse')->assertOk()->assertSee('ngoi-pulse-network', false);
});
