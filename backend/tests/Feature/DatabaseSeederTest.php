<?php

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Message;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Faker\Generator;

/** Giả lập image Docker production: không có Faker, APP_ENV=production. */
function runProductionSeed(array $env): void
{
    app()->instance('env', 'production');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
    // Faker chỉ cài ở require-dev: mọi lần gọi fake() trong seeder phải lỗi như trong image thật.
    app()->bind(Generator::class.':'.config('app.faker_locale', 'en_US'), fn () => throw new Error('Call to undefined function fake()'));

    test()->artisan('db:seed', ['--force' => true])->assertSuccessful();
}

afterEach(function () {
    foreach (['SEED_ADMIN_PASSWORD', 'SEED_DEMO_DATA'] as $key) {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }
});

it('seed được ở production khi không có Faker, chỉ tạo danh mục và chủ tiệm', function () {
    runProductionSeed(['SEED_ADMIN_PASSWORD' => 'Mat-khau-manh-123']);

    expect(Branch::count())->toBeGreaterThan(0)
        ->and(User::where('email', 'admin@ngoi.vn')->exists())->toBeTrue()
        ->and(User::count())->toBe(1)
        ->and(Booking::count())->toBe(0)
        ->and(Message::count())->toBe(0);
});

it('SEED_DEMO_DATA=true tạo booking và lời nhắn mẫu mà không cần Faker', function () {
    runProductionSeed(['SEED_ADMIN_PASSWORD' => 'Mat-khau-manh-123', 'SEED_DEMO_DATA' => 'true']);

    expect(Booking::count())->toBeGreaterThan(100)
        ->and(Message::count())->toBe(6)
        ->and(Booking::whereNull('user_id')->where('customer_phone', 'like', '09%')->exists())->toBeTrue();
});

it('chạy local thì luôn có dữ liệu mẫu', function () {
    expect(DatabaseSeeder::shouldSeedDemoData())->toBeTrue();
});
