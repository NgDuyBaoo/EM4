<?php

namespace Database\Seeders;

use Illuminate\Support\Arr;

/**
 * Tên, số điện thoại mẫu cho seeder.
 * Không dùng Faker vì Faker là gói dev, không có trong image Docker production.
 */
final class DemoData
{
    private const HO = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Võ', 'Đặng', 'Bùi', 'Đỗ'];

    private const DEM = ['Thị', 'Văn', 'Minh', 'Ngọc', 'Thanh', 'Gia', 'Bảo', 'Hoài', 'Quốc', 'Thu'];

    private const TEN = ['An', 'Bình', 'Chi', 'Dũng', 'Hà', 'Hân', 'Khoa', 'Linh', 'My', 'Nam', 'Phúc', 'Quân', 'Trang', 'Vy', 'Yến'];

    public static function name(): string
    {
        return Arr::random(self::HO).' '.Arr::random(self::DEM).' '.Arr::random(self::TEN);
    }

    public static function phone(): string
    {
        return '09'.str_pad((string) random_int(0, 99_999_999), 8, '0', STR_PAD_LEFT);
    }

    /** true với xác suất $percent %. */
    public static function chance(int $percent): bool
    {
        return random_int(1, 100) <= $percent;
    }
}
