<?php

use App\Exceptions\BookingException;
use App\Jobs\SendErrorAlert;
use App\Notifications\ErrorOccurred;
use Illuminate\Database\QueryException;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/_test/loi', fn () => throw new RuntimeException('Hết bộ nhớ khi tạo báo cáo'));
    Route::get('/_test/loi-nghiep-vu', fn () => throw new BookingException('SLOT_TAKEN', 'Slot đã có người đặt'));
    Route::get('/_test/loi-sql', fn () => throw new QueryException('sqlite', 'select * from users where phone = ?', ['0912345678'], new Exception('no such column')));
});

it('lỗi 500 đẩy job báo cho chủ tiệm qua queue', function () {
    Queue::fake();

    $this->get('/_test/loi')->assertStatus(500);

    Queue::assertPushed(SendErrorAlert::class, fn ($job) => $job->exception === RuntimeException::class
        && $job->message === 'Hết bộ nhớ khi tạo báo cáo'
        && $job->where === 'GET _test/loi'
        && str_starts_with($job->file, 'tests/'));
});

it('cùng một lỗi chỉ báo một lần trong khoảng chống spam', function () {
    Queue::fake();

    $this->get('/_test/loi');
    $this->get('/_test/loi');

    Queue::assertPushed(SendErrorAlert::class, 1);
});

it('không báo lỗi nghiệp vụ, 404, 401', function () {
    Queue::fake();

    $this->getJson('/_test/loi-nghiep-vu')->assertStatus(409);
    $this->get('/khong-co-trang-nay')->assertNotFound();
    $this->getJson('/api/admin/access-code')->assertUnauthorized();
    Queue::assertNothingPushed();
});

it('không gửi giá trị trong câu SQL lỗi (dữ liệu khách)', function () {
    Queue::fake();

    $this->get('/_test/loi-sql')->assertStatus(500);

    Queue::assertPushed(SendErrorAlert::class, fn ($job) => $job->message === 'Lỗi SQL: select * from users where phone = ?'
        && ! str_contains($job->message, '0912345678'));
});

it('tắt cảnh báo bằng cấu hình', function () {
    Queue::fake();
    config(['ngoi.error_alert.enabled' => false]);

    $this->get('/_test/loi')->assertStatus(500);

    Queue::assertNothingPushed();
});

it('job gửi email cho chủ cửa hàng và Slack', function () {
    Notification::fake();
    Http::fake(['hooks.slack.com/*' => Http::response('ok')]);
    config(['ngoi.alerts.slack_webhook' => 'https://hooks.slack.com/services/T/B/X']);
    $owner = owner();
    $staff = staff();

    (new SendErrorAlert(RuntimeException::class, 'Hết bộ nhớ', 'app/Services/ReportService.php', 42, 'GET admin/thong-ke'))->handle();

    Notification::assertSentTo($owner, ErrorOccurred::class, fn ($n) => $n->toMail($owner)->subject === '[Ngói] Lỗi hệ thống: RuntimeException');
    Notification::assertNotSentTo($staff, ErrorOccurred::class);
    Http::assertSent(fn ($req) => str_contains($req['text'], 'Lỗi 500 tại GET admin/thong-ke')
        && str_contains($req['text'], 'app/Services/ReportService.php:42'));
});

it('gửi tới email cấu hình riêng nếu có', function () {
    Notification::fake();
    config(['ngoi.alerts.mail_to' => 'dev@ngoi.vn']);

    (new SendErrorAlert(RuntimeException::class, 'x', 'app/a.php', 1, 'console/queue'))->handle();

    Notification::assertSentTo(new AnonymousNotifiable, ErrorOccurred::class,
        fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === 'dev@ngoi.vn');
});
