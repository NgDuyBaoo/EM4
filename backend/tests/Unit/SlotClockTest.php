<?php

use App\Enums\BookingStatus;
use App\Support\Phone;
use App\Support\SlotClock;
use Illuminate\Support\Carbon;

it('tạo 84 slot 10 phút từ 08:30 đến 22:30', function () {
    $slots = SlotClock::slots('08:30', '22:30');
    expect($slots)->toHaveCount(84)
        ->and($slots[0])->toBe('08:30')
        ->and($slots[1])->toBe('08:40')
        ->and(end($slots))->toBe('22:20');
});

it('chỉ chấp nhận giờ nằm đúng lưới slot', function (string $time, bool $ok) {
    expect(SlotClock::isValidSlot($time, '08:30', '22:30'))->toBe($ok);
})->with([
    ['14:30', true], ['08:30', true], ['22:20', true],
    ['14:35', false], ['08:20', false], ['22:30', false], ['1430', false],
]);

it('tính slot đã bắt đầu / đã kết thúc theo giờ Việt Nam', function () {
    $this->travelTo(Carbon::parse('2026-10-16 07:35:00', 'UTC')); // 14:35 giờ VN
    expect(SlotClock::today())->toBe('2026-10-16')
        ->and(SlotClock::started('14:30'))->toBeTrue()
        ->and(SlotClock::ended('14:30'))->toBeFalse()
        ->and(SlotClock::ended('14:20'))->toBeTrue()
        ->and(SlotClock::started('14:40'))->toBeFalse()
        ->and(SlotClock::started('09:00', '2026-10-15'))->toBeTrue();
});

it('qua nửa đêm giờ VN là sang ngày mới dù UTC vẫn là hôm trước', function () {
    $this->travelTo(Carbon::parse('2026-10-15 17:30:00', 'UTC'));
    expect(SlotClock::today())->toBe('2026-10-16');
});

it('chuẩn hóa số điện thoại Việt Nam', function (string $in, ?string $out) {
    expect(Phone::normalize($in))->toBe($out);
})->with([
    ['0912 345 678', '0912345678'], ['+84912345678', '0912345678'], ['84912345678', '0912345678'],
    ['0912.345.678', '0912345678'], ['12345', null], ['09123456789', null],
]);

it('vòng đời trạng thái booking chỉ cho chuyển hợp lệ', function () {
    expect(BookingStatus::Booked->canTransitionTo(BookingStatus::CheckedIn))->toBeTrue()
        ->and(BookingStatus::Booked->canTransitionTo(BookingStatus::Completed))->toBeFalse()
        ->and(BookingStatus::CheckedIn->canTransitionTo(BookingStatus::Completed))->toBeTrue()
        ->and(BookingStatus::Completed->canTransitionTo(BookingStatus::Cancelled))->toBeFalse()
        ->and(BookingStatus::Cancelled->next())->toBe([]);
});
