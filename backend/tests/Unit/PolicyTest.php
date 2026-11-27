<?php

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Policies\BookingPolicy;
use App\Policies\RoomPolicy;

function userWith(string $state, int $id): User
{
    $factory = User::factory();
    $user = ($state === 'customer' ? $factory : $factory->{$state}())->make();
    $user->id = $id;

    return $user;
}

it('khách chỉ xem và sửa được booking của chính mình', function () {
    $policy = new BookingPolicy;
    $me = userWith('customer', 1);
    $mine = new Booking(['user_id' => 1]);
    $other = new Booking(['user_id' => 2]);

    expect($policy->view($me, $mine))->toBeTrue()
        ->and($policy->view($me, $other))->toBeFalse()
        ->and($policy->update($me, $other))->toBeFalse()
        ->and($policy->delete($me, $mine))->toBeFalse();
});

it('nhân viên xem và sửa mọi booking nhưng chỉ chủ tiệm được xóa', function () {
    $policy = new BookingPolicy;
    $booking = new Booking(['user_id' => 99]);

    expect($policy->update(userWith('staff', 2), $booking))->toBeTrue()
        ->and($policy->delete(userWith('staff', 2), $booking))->toBeFalse()
        ->and($policy->delete(userWith('owner', 3), $booking))->toBeTrue();
});

it('chỉ chủ tiệm được thêm, sửa, xóa phòng', function (string $role, bool $ok) {
    $policy = new RoomPolicy;
    $user = userWith($role, 5);
    $room = new Room;

    expect($policy->create($user))->toBe($ok)
        ->and($policy->update($user, $room))->toBe($ok)
        ->and($policy->delete($user, $room))->toBe($ok);
})->with([['customer', false], ['staff', false], ['owner', true]]);
