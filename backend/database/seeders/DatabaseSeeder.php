<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CatalogSeeder::class,   // 2 chi nhánh, 6 phòng từ data/catalog
            UserSeeder::class,      // chủ cửa hàng (+ nhân viên, khách mẫu khi chạy local)
        ]);

        // Dữ liệu mẫu chỉ dùng khi chạy thử: local/testing, hoặc đặt SEED_DEMO_DATA=true (ví dụ Docker để chụp ảnh demo).
        if (self::shouldSeedDemoData()) {
            $this->call([
                BookingSeeder::class,   // booking mẫu 30 ngày qua + hôm nay
                MessageSeeder::class,   // lời nhắn mẫu
            ]);
        }
    }

    public static function shouldSeedDemoData(): bool
    {
        return app()->environment('local', 'testing')
            || filter_var(env('SEED_DEMO_DATA', false), FILTER_VALIDATE_BOOLEAN);
    }
}
