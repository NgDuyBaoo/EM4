<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Tài khoản mẫu mật khẩu 'password' chỉ dùng khi chạy local/test. Triển khai thật phải đặt SEED_ADMIN_PASSWORD.
        $demo = app()->environment('local', 'testing');
        $password = env('SEED_ADMIN_PASSWORD') ?: ($demo ? 'password' : null);
        if ($password === null) {
            throw new \RuntimeException('Chưa đặt SEED_ADMIN_PASSWORD: không seed tài khoản chủ cửa hàng với mật khẩu mặc định ở môi trường '.app()->environment().'.');
        }

        $owner = User::firstOrNew(['email' => env('SEED_ADMIN_EMAIL', 'admin@ngoi.vn')]);
        $owner->fill(['name' => 'Chủ Ngói', 'phone' => '0900000001', 'password' => $password]);
        $owner->role = UserRole::Owner;
        $owner->email_verified_at = now();
        $owner->save();

        if (! $demo) {
            return;
        }

        $staff = User::firstOrNew(['email' => 'nhanvien@ngoi.vn']);
        $staff->fill(['name' => 'Nhân viên quầy', 'phone' => '0900000002', 'password' => 'password']);
        $staff->role = UserRole::Staff;
        $staff->save();

        User::firstOrCreate(['email' => 'khach@ngoi.vn'], ['name' => 'Khách Demo', 'phone' => '0912345678', 'password' => 'password']);

        if (User::where('role', UserRole::Customer)->count() < 20) {
            User::factory(20)->create();
        }
    }
}
