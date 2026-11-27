<?php

use App\Models\User;

it('đăng ký tài khoản khách và nhận token', function () {
    $res = $this->postJson('/api/auth/register', [
        'name' => 'Nguyễn A', 'email' => 'a@example.com', 'phone' => '0912345678',
        'password' => 'matkhau123', 'password_confirmation' => 'matkhau123',
    ])->assertCreated()->assertJsonPath('user.role', 'customer');

    $this->withToken($res->json('token'))->getJson('/api/auth/me')->assertOk()->assertJsonPath('email', 'a@example.com');
});

it('kiểm tra dữ liệu đăng ký phía server', function () {
    $this->postJson('/api/auth/register', ['email' => 'sai', 'password' => '1', 'phone' => '123'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'password', 'phone']);
});

it('đăng nhập sai báo lỗi, đúng nhận token; đăng xuất thu hồi token', function () {
    $user = User::factory()->create(['email' => 'b@example.com']);
    $this->postJson('/api/auth/login', ['email' => 'b@example.com', 'password' => 'sai'])->assertUnprocessable();
    $token = $this->postJson('/api/auth/login', ['email' => 'b@example.com', 'password' => 'password'])->assertOk()->json('token');

    $this->withToken($token)->postJson('/api/auth/logout')->assertOk();
    expect($user->tokens()->count())->toBe(0);
});

it('chặn API cần đăng nhập khi không có token', function () {
    $this->getJson('/api/bookings')->assertUnauthorized()->assertJsonPath('code', 'UNAUTHENTICATED');
});
