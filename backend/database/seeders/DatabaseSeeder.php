<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CatalogSeeder::class,   // 2 chi nhánh, 6 phòng từ data/catalog
            UserSeeder::class,      // chủ cửa hàng, nhân viên, khách mẫu
            BookingSeeder::class,   // booking mẫu 30 ngày qua + hôm nay
            MessageSeeder::class,   // lời nhắn mẫu
        ]);
    }
}
