<?php

use App\Support\Phone;

it('chuẩn hóa số điện thoại Việt Nam về dạng 0xxxxxxxxx', function (?string $input, ?string $expected) {
    expect(Phone::normalize($input))->toBe($expected);
})->with([
    'đã đúng dạng' => ['0912345678', '0912345678'],
    'có khoảng trắng' => ['0912 345 678', '0912345678'],
    'có dấu chấm, gạch' => ['0912.345-678', '0912345678'],
    'đầu +84' => ['+84912345678', '0912345678'],
    'đầu 84' => ['84912345678', '0912345678'],
    'thiếu số' => ['091234567', null],
    'thừa số' => ['09123456789', null],
    'có chữ' => ['09123abc78', null],
    'rỗng' => ['', null],
    'null' => [null, null],
]);
