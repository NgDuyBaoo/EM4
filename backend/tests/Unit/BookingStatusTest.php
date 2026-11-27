<?php

use App\Enums\BookingStatus;
use App\Enums\UserRole;

it('chỉ cho chuyển trạng thái theo đúng vòng đời booking', function (BookingStatus $from, BookingStatus $to, bool $ok) {
    expect($from->canTransitionTo($to))->toBe($ok);
})->with([
    [BookingStatus::Booked, BookingStatus::CheckedIn, true],
    [BookingStatus::Booked, BookingStatus::Cancelled, true],
    [BookingStatus::Booked, BookingStatus::Completed, false],
    [BookingStatus::CheckedIn, BookingStatus::Completed, true],
    [BookingStatus::CheckedIn, BookingStatus::Cancelled, true],
    [BookingStatus::CheckedIn, BookingStatus::Booked, false],
    [BookingStatus::Completed, BookingStatus::Cancelled, false],
    [BookingStatus::Cancelled, BookingStatus::Booked, false],
]);

it('trạng thái đã hủy là không còn hiệu lực, các trạng thái khác vẫn giữ slot', function () {
    expect(BookingStatus::Cancelled->isActive())->toBeFalse()
        ->and(BookingStatus::Booked->isActive())->toBeTrue()
        ->and(BookingStatus::CheckedIn->isActive())->toBeTrue()
        ->and(BookingStatus::Completed->isActive())->toBeTrue();
});

it('mỗi trạng thái có nhãn tiếng Việt và màu riêng', function () {
    $labels = array_map(fn ($s) => $s->label(), BookingStatus::cases());
    $badges = array_map(fn ($s) => $s->badge(), BookingStatus::cases());
    expect(array_unique($labels))->toHaveCount(4)
        ->and(array_unique($badges))->toHaveCount(4);
});

it('chỉ khách hàng không phải nhân viên', function () {
    expect(UserRole::Customer->isStaff())->toBeFalse()
        ->and(UserRole::Staff->isStaff())->toBeTrue()
        ->and(UserRole::Owner->isStaff())->toBeTrue();
});
