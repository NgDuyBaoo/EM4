<?php

use App\Jobs\SendSlowQueryAlert;
use App\Monitoring\SlowQueryAlert;
use App\Notifications\SlowQueryDetected;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

it('truy vấn chậm hơn ngưỡng thì đẩy job cảnh báo vào queue', function () {
    Queue::fake();
    config(['ngoi.slow_query_ms' => 0]);

    DB::select('select 1 as cham');

    Queue::assertPushed(SendSlowQueryAlert::class, fn ($job) => $job->kind === 'query' && str_contains($job->sql, 'select 1 as cham'));
});

it('cùng một câu SQL chỉ báo một lần trong khoảng chống spam', function () {
    Queue::fake();
    config(['ngoi.slow_query_ms' => 0]);

    DB::select('select 2 as lap');
    DB::select('select 2 as lap');
    DB::select('select 3 as khac');

    Queue::assertPushed(SendSlowQueryAlert::class, fn ($job) => str_contains($job->sql, 'select 2 as lap'));
    Queue::assertPushed(SendSlowQueryAlert::class, 2);
});

it('không gửi khi tắt cảnh báo hoặc truy vấn nhanh hơn ngưỡng', function () {
    Queue::fake();

    config(['ngoi.slow_query_ms' => 100000]);
    DB::select('select 4');
    config(['ngoi.slow_query_ms' => 0, 'ngoi.slow_query_alert.enabled' => false]);
    DB::select('select 5');

    Queue::assertNothingPushed();
});

it('gửi email cho các tài khoản chủ cửa hàng', function () {
    Notification::fake();
    $owner = owner();
    $staff = staff();

    (new SendSlowQueryAlert('query', 'select * from bookings', 812.5, 'http://ngoi.test/admin'))->handle();

    Notification::assertSentTo($owner, SlowQueryDetected::class, function ($n) use ($owner) {
        $mail = $n->toMail($owner);

        return $mail->subject === '[Ngói] Cảnh báo truy vấn chậm: 813 ms'
            && collect($mail->introLines)->contains('SQL: select * from bookings');
    });
    Notification::assertNotSentTo($staff, SlowQueryDetected::class);
});

it('gửi tới email cấu hình riêng và Slack webhook nếu có', function () {
    Notification::fake();
    Http::fake(['hooks.slack.com/*' => Http::response('ok')]);
    config([
        'ngoi.slow_query_alert.mail_to' => 'ops@ngoi.vn',
        'ngoi.slow_query_alert.slack_webhook' => 'https://hooks.slack.com/services/T/B/X',
    ]);

    (new SendSlowQueryAlert('request', 'select * from rooms', 2300, 'http://ngoi.test/'))->handle();

    Notification::assertSentTo(new AnonymousNotifiable, SlowQueryDetected::class,
        fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === 'ops@ngoi.vn');
    Http::assertSent(fn ($req) => str_contains($req['text'], 'Request có tổng thời gian truy vấn 2300 ms'));
});

it('Slack lỗi thì vẫn gửi được email, không làm hỏng job', function () {
    Notification::fake();
    Http::fake(['*' => Http::response('lỗi', 500)]);
    config(['ngoi.slow_query_alert.slack_webhook' => 'https://hooks.slack.com/services/T/B/X']);
    $owner = owner();

    (new SendSlowQueryAlert('query', 'select 1', 600))->handle();

    Notification::assertSentTo($owner, SlowQueryDetected::class);
});

it('truy vấn phát sinh khi đang gửi cảnh báo không tạo cảnh báo mới (không lặp vô hạn)', function () {
    Notification::fake();
    owner();
    config(['ngoi.slow_query_ms' => 0, 'queue.default' => 'sync']);

    SlowQueryAlert::report('query', 'select * from users', 900);

    Notification::assertCount(1);
});
