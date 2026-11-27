<?php

use App\Enums\BookingStatus;
use App\Jobs\SendBookingConfirmation;
use App\Models\Booking;
use App\Notifications\BookingConfirmed;
use App\Services\AvailabilityService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    [$this->b1] = seedBranches();
    $this->room = $this->b1->rooms->first();
});

it('lệnh lập lịch tự hủy booking quá 15 phút chưa check-in (BR-07)', function () {
    freezeAt('14:50');
    $late = Booking::factory()->for($this->room)->at('14:30')->create(['branch_id' => $this->b1->id]);
    $soon = Booking::factory()->for($this->room)->at('14:40')->create(['branch_id' => $this->b1->id]);
    $in = Booking::factory()->for($this->room)->at('14:20')->status(BookingStatus::CheckedIn)->create(['branch_id' => $this->b1->id]);

    $this->artisan('bookings:cancel-no-shows')->expectsOutputToContain('Đã hủy 1')->assertSuccessful();
    expect($late->fresh()->status)->toBe(BookingStatus::Cancelled)
        ->and($late->fresh()->cancel_reason)->toContain('quá 15 phút')
        ->and($soon->fresh()->status)->toBe(BookingStatus::Booked)
        ->and($in->fresh()->status)->toBe(BookingStatus::CheckedIn);
});

it('lệnh tự hủy được đăng ký chạy mỗi 5 phút', function () {
    $events = collect(app(Schedule::class)->events());
    expect($events->first(fn ($e) => str_contains($e->command, 'bookings:cancel-no-shows'))->expression)->toBe('*/5 * * * *');
});

it('job gửi email xác nhận chạy được và gửi tới đúng khách', function () {
    Notification::fake();
    $u = customer();
    Booking::factory()->for($this->room)->create(['branch_id' => $this->b1->id, 'user_id' => $u->id, 'booking_code' => 'NGTEST01']);
    (new SendBookingConfirmation('NGTEST01', $u->id))->handle();
    Notification::assertSentTo($u, BookingConfirmed::class, fn ($n) => $n->toMail($u)->subject === 'Ngói Photobooth – Xác nhận lịch chụp NGTEST01');
});

it('cache lịch trống được xóa ngay khi có booking mới', function () {
    freezeAt('10:00');
    $svc = app(AvailabilityService::class);
    $before = $svc->forBranch($this->b1);
    expect(collect($before['rooms'][0]['slots'])->firstWhere('time', '15:00')['state'])->toBe('free');
    Booking::factory()->for($this->room)->at('15:00')->create(['branch_id' => $this->b1->id]);
    $after = $svc->forBranch($this->b1);
    expect(collect($after['rooms'][0]['slots'])->firstWhere('time', '15:00')['state'])->toBe('booked');
});

it('ghi log cảnh báo khi truy vấn chậm hơn ngưỡng', function () {
    config(['ngoi.slow_query_ms' => 0]);
    $log = Mockery::spy();
    Log::shouldReceive('channel')->with('slow-query')->andReturn($log);
    Log::shouldReceive('channel')->withAnyArgs()->andReturn($log);
    DB::select('select 1');
    $log->shouldHaveReceived('warning')->withArgs(fn ($msg) => $msg === 'Truy vấn chậm');
});
