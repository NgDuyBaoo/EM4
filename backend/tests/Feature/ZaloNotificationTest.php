<?php

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Jobs\SendBookingZalo;
use App\Models\Booking;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingReminder;
use App\Services\BookingService;
use App\Services\Zalo\ZaloException;
use App\Services\Zalo\ZaloZns;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    freezeAt('10:00');
    [$this->branch] = seedBranches();
    $this->room = $this->branch->rooms->first();
    config([
        'services.zalo.driver' => 'zns',
        'services.zalo.mode' => null,
        'services.zalo.app_id' => 'app-test',
        'services.zalo.secret_key' => 'secret-test',
        'services.zalo.access_token' => 'access-test',
        'services.zalo.refresh_token' => 'refresh-test',
        'services.zalo.templates' => ['confirmed' => '1001', 'reminder' => '1002', 'cancelled' => '1003'],
    ]);
});

function znsOk(): array
{
    return ['error' => 0, 'message' => 'Success', 'data' => ['msg_id' => 'msg-1', 'sent_time' => '1700000000000']];
}

it('đặt lịch online gửi tin ZNS xác nhận tới SĐT khách, đúng mẫu và tham số', function () {
    Http::fake(['business.openapi.zalo.me/*' => Http::response(znsOk())]);

    app(BookingService::class)->create($this->branch, [
        ['room_id' => $this->room->id, 'time' => '14:00'],
        ['room_id' => $this->room->id, 'time' => '14:10'],
    ], 'Nguyễn Thị Lan', '0912 345 678');

    Http::assertSentCount(1);
    Http::assertSent(function (Request $r) {
        $d = $r['template_data'];

        return $r->url() === 'https://business.openapi.zalo.me/message/template'
            && $r->header('access_token') === ['access-test']
            && $r['phone'] === '84912345678'
            && $r['template_id'] === '1001'
            && ! isset($r['mode'])
            && $d['customer_name'] === 'Nguyễn Thị Lan'
            && $d['booking_date'] === '16/10/2026'
            && $d['slot_time'] === '14:00'
            && str_contains($d['slots'], '14:00') && str_contains($d['slots'], '14:10')
            && $d['total'] === '100000'
            && str_starts_with($r['tracking_id'], $d['booking_code']);
    });
});

it('khách đặt tại quầy (walk-in) không nhận tin xác nhận', function () {
    Queue::fake();
    app(BookingService::class)->create($this->branch, [['room_id' => $this->room->id, 'time' => '14:00']], 'Lan', '0912345678', BookingSource::Walkin);
    Queue::assertNothingPushed();
});

it('nhân viên hủy lịch thì khách nhận tin báo hủy, khách tự hủy thì không', function () {
    Notification::fake();
    $svc = app(BookingService::class);
    $a = Booking::factory()->for($this->room)->at('14:00')->create(['branch_id' => $this->branch->id, 'customer_phone' => '0911111111']);
    $b = Booking::factory()->for($this->room)->at('15:00')->create(['branch_id' => $this->branch->id, 'customer_phone' => '0922222222']);

    $svc->transition($a, BookingStatus::Cancelled, 'Tiệm mất điện');
    $svc->customerCancel($b);

    Notification::assertSentOnDemandTimes(BookingCancelled::class, 1);
    Notification::assertSentOnDemand(BookingCancelled::class, fn ($n, $c, $to) => $to->routes['zalo'] === '0911111111'
        && $n->toZalo($to)->data['cancel_reason'] === 'Tiệm mất điện');
});

it('tự hủy vì quá giờ gửi 1 tin cho mỗi mã booking', function () {
    Notification::fake();
    freezeAt('14:50');
    foreach (['14:20', '14:30'] as $t) {
        Booking::factory()->for($this->room)->at($t)->create(['branch_id' => $this->branch->id, 'booking_code' => 'NGLATE01']);
    }

    expect(app(BookingService::class)->cancelNoShows())->toBe(2);
    Notification::assertSentOnDemandTimes(BookingCancelled::class, 1);
    Notification::assertSentOnDemand(BookingCancelled::class, fn ($n) => $n->bookings->count() === 2);
});

it('nhắc lịch trước giờ chụp đúng 1 lần cho mỗi mã, bỏ qua khách đặt sát giờ', function () {
    Notification::fake();
    config(['ngoi.reminder_minutes' => 30]);
    freezeAt('09:00');
    $soon = Booking::factory()->for($this->room)->at('10:20')->create(['branch_id' => $this->branch->id, 'booking_code' => 'NGSOON01']);
    Booking::factory()->for($this->room)->at('10:30')->create(['branch_id' => $this->branch->id, 'booking_code' => 'NGSOON01']);
    $later = Booking::factory()->for($this->room)->at('11:30')->create(['branch_id' => $this->branch->id]);
    $walkin = Booking::factory()->for($this->room)->at('10:10')->create(['branch_id' => $this->branch->id, 'source' => BookingSource::Walkin]);

    freezeAt('10:00');
    $late = Booking::factory()->for($this->branch->rooms[1])->at('10:20')->create(['branch_id' => $this->branch->id]);

    $this->artisan('bookings:send-reminders')->expectsOutputToContain('Đã gửi 1 tin')->assertSuccessful();
    $this->artisan('bookings:send-reminders')->expectsOutputToContain('Đã gửi 0 tin')->assertSuccessful();

    Notification::assertSentOnDemandTimes(BookingReminder::class, 1);
    Notification::assertSentOnDemand(BookingReminder::class, fn ($n) => $n->bookings->pluck('booking_code')->unique()->all() === ['NGSOON01'] && $n->bookings->count() === 2);
    expect($soon->fresh()->reminded_at)->not->toBeNull()
        ->and($late->fresh()->reminded_at)->not->toBeNull()
        ->and($later->fresh()->reminded_at)->toBeNull()
        ->and($walkin->fresh()->reminded_at)->toBeNull();
});

it('lệnh nhắc lịch được đăng ký chạy mỗi phút', function () {
    $events = collect(app(Schedule::class)->events());
    expect($events->first(fn ($e) => str_contains($e->command, 'bookings:send-reminders'))->expression)->toBe('* * * * *');
});

it('access token hết hạn thì tự làm mới bằng refresh token rồi gửi lại', function () {
    Http::fake([
        'business.openapi.zalo.me/*' => Http::sequence()
            ->push(['error' => -124, 'message' => 'Access token is invalid'])
            ->push(znsOk()),
        'oauth.zaloapp.com/*' => Http::response(['access_token' => 'access-new', 'refresh_token' => 'refresh-new', 'expires_in' => '90000']),
    ]);

    expect(app(ZaloZns::class)->send('0912345678', '1001', ['customer_name' => 'Lan'], 'NGTEST01'))->toBe('msg-1')
        ->and(Cache::get(ZaloZns::ACCESS_KEY))->toBe('access-new')
        ->and(Cache::get(ZaloZns::REFRESH_KEY))->toBe('refresh-new');

    Http::assertSent(fn (Request $r) => str_contains($r->url(), 'oauth.zaloapp.com')
        && $r->header('secret_key') === ['secret-test']
        && $r['refresh_token'] === 'refresh-test' && $r['app_id'] === 'app-test' && $r['grant_type'] === 'refresh_token');
    Http::assertSent(fn (Request $r) => str_contains($r->url(), 'message/template') && $r->header('access_token') === ['access-new']);
});

it('Zalo trả lỗi thì job ném lỗi để queue thử lại', function () {
    Http::fake(['business.openapi.zalo.me/*' => Http::response(['error' => -108, 'message' => 'Phone number is invalid'])]);
    $b = Booking::factory()->for($this->room)->create(['branch_id' => $this->branch->id]);

    expect(fn () => (new SendBookingZalo('confirmed', [$b->id]))->handle())->toThrow(ZaloException::class, 'Phone number is invalid');
});

it('chế độ development gửi kèm mode để thử với tài khoản quản trị', function () {
    config(['services.zalo.mode' => 'development']);
    Http::fake(['business.openapi.zalo.me/*' => Http::response(znsOk())]);
    app(ZaloZns::class)->send('0912345678', '1001', [], 'NGTEST01');
    Http::assertSent(fn (Request $r) => $r['mode'] === 'development');
});

it('driver log chỉ ghi log, không gọi Zalo', function () {
    config(['services.zalo.driver' => 'log']);
    Http::fake();
    $b = Booking::factory()->for($this->room)->create(['branch_id' => $this->branch->id]);

    (new SendBookingZalo('confirmed', [$b->id]))->handle();
    Http::assertNothingSent();
});

it('chưa có template_id thì bỏ qua tin đó, không gọi Zalo', function () {
    config(['services.zalo.templates.reminder' => null]);
    Http::fake();
    $b = Booking::factory()->for($this->room)->create(['branch_id' => $this->branch->id]);

    (new SendBookingZalo('reminder', [$b->id]))->handle();
    Http::assertNothingSent();
});

it('không còn gửi email xác nhận cho khách', function () {
    $b = Booking::factory()->for($this->room)->create(['branch_id' => $this->branch->id]);
    expect((new BookingConfirmed(collect([$b])))->via(new stdClass))->not->toContain('mail');
});
