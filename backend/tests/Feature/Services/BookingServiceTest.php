<?php

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Jobs\SendBookingConfirmation;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    freezeAt('10:00');
    [$this->branch] = seedBranches();
    $this->room = $this->branch->rooms->first();
    $this->service = app(BookingService::class);
});

/** Chạy $fn và trả về mã lỗi nghiệp vụ (BookingException::$errorCode). */
function bookingError(callable $fn): ?string
{
    try {
        $fn();
    } catch (BookingException $e) {
        return $e->errorCode;
    }

    return null;
}

it('tạo nhiều slot chung một mã booking và đẩy job gửi mail vào queue', function () {
    Queue::fake();
    $user = customer();

    $bookings = $this->service->create($this->branch, [
        ['room_id' => $this->room->id, 'time' => '14:00'],
        ['room_id' => $this->room->id, 'time' => '14:10'],
    ], 'Lan', '+84 912 345 678', BookingSource::Online, $user);

    expect($bookings)->toHaveCount(2)
        ->and($bookings->pluck('booking_code')->unique())->toHaveCount(1)
        ->and($bookings->first()->booking_code)->toMatch('/^NG[A-Z2-9]{6}$/')
        ->and($bookings->first()->customer_phone)->toBe('0912345678')
        ->and($bookings->first()->price)->toBe($this->room->price_per_slot);
    Queue::assertPushed(SendBookingConfirmation::class, fn ($job) => $job->userId === $user->id);
});

it('từ chối dữ liệu sai trước khi ghi vào database', function (array $times, string $phone, string $code) {
    $items = array_map(fn ($t) => ['room_id' => $t === 'other-room' ? 999 : $this->room->id, 'time' => $t === 'other-room' ? '14:00' : $t], $times);

    expect(bookingError(fn () => $this->service->create($this->branch, $items, 'Lan', $phone)))->toBe($code)
        ->and(Booking::count())->toBe(0);
})->with([
    'SĐT sai' => [['14:00'], '123', 'INVALID_PHONE'],
    'không chọn slot' => [[], '0912345678', 'NO_SLOT'],
    'giờ lệch lưới 10 phút' => [['14:05'], '0912345678', 'INVALID_SLOT'],
    'slot đã bắt đầu' => [['09:50'], '0912345678', 'SLOT_PAST'],
    'chọn trùng 2 lần' => [['14:00', '14:00'], '0912345678', 'DUPLICATE_SLOT'],
    'phòng không thuộc chi nhánh' => [['other-room'], '0912345678', 'INVALID_ROOM'],
]);

it('giới hạn số slot mỗi lần đặt online', function () {
    config(['ngoi.max_slots_per_booking' => 2]);
    $items = array_map(fn ($t) => ['room_id' => $this->room->id, 'time' => $t], ['14:00', '14:10', '14:20']);

    expect(bookingError(fn () => $this->service->create($this->branch, $items, 'Lan', '0912345678')))->toBe('TOO_MANY_SLOTS');
});

it('không cho hai khách đặt cùng một slot', function () {
    $item = [['room_id' => $this->room->id, 'time' => '15:00']];
    $this->service->create($this->branch, $item, 'Lan', '0912345678');

    expect(bookingError(fn () => $this->service->create($this->branch, $item, 'Minh', '0987654321')))->toBe('SLOT_TAKEN')
        ->and(Booking::count())->toBe(1);
});

it('slot đã hủy có thể được đặt lại', function () {
    $item = [['room_id' => $this->room->id, 'time' => '15:00']];
    $first = $this->service->create($this->branch, $item, 'Lan', '0912345678')->first();
    $this->service->transition($first, BookingStatus::Cancelled, 'Khách báo bận');

    expect($this->service->create($this->branch, $item, 'Minh', '0987654321'))->toHaveCount(1);
});

it('đổi trạng thái theo vòng đời và ghi thời điểm', function () {
    $booking = Booking::factory()->for($this->room)->at('14:00')->create(['branch_id' => $this->branch->id]);

    $checked = $this->service->transition($booking, BookingStatus::CheckedIn);
    expect($checked->checked_in_at)->not->toBeNull();

    $done = $this->service->transition($checked, BookingStatus::Completed);
    expect($done->status)->toBe(BookingStatus::Completed)->and($done->completed_at)->not->toBeNull();

    expect(bookingError(fn () => $this->service->transition($done, BookingStatus::Cancelled)))->toBe('INVALID_TRANSITION');
});

it('khách chỉ tự hủy được lịch chưa tới giờ', function () {
    $future = Booking::factory()->for($this->room)->at('14:00')->create(['branch_id' => $this->branch->id]);
    $started = Booking::factory()->for($this->room)->at('09:50')->create(['branch_id' => $this->branch->id]);

    expect($this->service->customerCancel($future)->cancel_reason)->toBe('Khách tự hủy');
    expect(bookingError(fn () => $this->service->customerCancel($started)))->toBe('NOT_CANCELLABLE');
});
