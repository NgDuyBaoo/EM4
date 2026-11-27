<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\AccessCodeService;
use App\Services\AvailabilityService;

beforeEach(function () {
    freezeAt('10:00');
    [$this->branch] = seedBranches();
    $this->room = $this->branch->rooms->first();
});

function stateOf(array $result, int $roomId, string $time): string
{
    $room = collect($result['rooms'])->firstWhere('id', $roomId);

    return collect($room['slots'])->firstWhere('time', $time)['state'];
}

it('đánh dấu slot đã qua, đã đặt và còn trống', function () {
    Booking::factory()->for($this->room)->at('14:00')->create(['branch_id' => $this->branch->id]);
    Booking::factory()->for($this->room)->at('14:10')->status(BookingStatus::Cancelled)->create(['branch_id' => $this->branch->id]);

    $result = app(AvailabilityService::class)->forBranch($this->branch);

    expect(stateOf($result, $this->room->id, '09:00'))->toBe('past')
        ->and(stateOf($result, $this->room->id, '14:00'))->toBe('booked')
        ->and(stateOf($result, $this->room->id, '14:10'))->toBe('free')
        ->and($result['rooms'])->toHaveCount(3);
});

it('xóa cache lịch trống ngay khi có booking mới', function () {
    $service = app(AvailabilityService::class);
    expect(stateOf($service->forBranch($this->branch), $this->room->id, '16:00'))->toBe('free');

    Booking::factory()->for($this->room)->at('16:00')->create(['branch_id' => $this->branch->id]);

    expect(stateOf($service->forBranch($this->branch), $this->room->id, '16:00'))->toBe('booked');
});

it('mã truy cập 6 số giữ nguyên trong ngày và đổi khi tạo lại', function () {
    $service = app(AccessCodeService::class);
    $code = $service->today()->code;

    expect($code)->toMatch('/^\d{6}$/')
        ->and($service->today()->code)->toBe($code)
        ->and($service->verify($code))->toBeTrue()
        ->and($service->verify(' '.$code.' '))->toBeTrue()
        ->and($service->verify('abc'))->toBeFalse();

    do {
        $new = $service->regenerate()->code;
    } while ($new === $code);

    expect($service->verify($code))->toBeFalse()->and($service->verify($new))->toBeTrue();
});

it('mã truy cập tự đổi sang ngày mới theo giờ Việt Nam', function () {
    $service = app(AccessCodeService::class);
    expect((string) $service->today()->getKey())->toStartWith('2026-10-16');

    freezeAt('00:05', '2026-10-17');

    expect((string) $service->today()->getKey())->toStartWith('2026-10-17');
});
