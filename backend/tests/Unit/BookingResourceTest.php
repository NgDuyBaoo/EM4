<?php

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Illuminate\Http\Request;

it('API trả booking đúng định dạng và không kèm quan hệ chưa nạp', function () {
    $booking = new Booking([
        'booking_code' => 'NGAB12CD', 'booking_date' => '2026-10-16', 'slot_time' => '14:30:00',
        'status' => BookingStatus::Booked, 'source' => BookingSource::Online,
        'customer_name' => 'Lan', 'customer_phone' => '0912345678', 'price' => 50000, 'note' => '',
    ]);
    $booking->id = 7;

    $data = (new BookingResource($booking))->resolve(Request::create('/'));

    expect($data)->toMatchArray([
        'id' => 7, 'code' => 'NGAB12CD', 'date' => '2026-10-16', 'time' => '14:30',
        'status' => 'booked', 'status_label' => 'Đã đặt', 'source' => 'online', 'price' => 50000,
        'checked_in_at' => null,
    ])->and($data)->not->toHaveKeys(['branch', 'room']);
});
