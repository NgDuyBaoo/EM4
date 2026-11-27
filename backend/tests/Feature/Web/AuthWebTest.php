<?php

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

it('hiển thị form đăng ký, đăng nhập có CSRF token', function () {
    $this->get('/dang-ky')->assertOk()->assertSee('name="_token"', false)->assertSee('Tạo tài khoản');
    $this->get('/dang-nhap')->assertOk()->assertSee('name="_token"', false);
});

it('đăng ký tài khoản mới, đăng nhập luôn và chuyển tới trang đặt lịch', function () {
    $this->post('/dang-ky', [
        'name' => 'Lê Văn C', 'email' => 'c@example.com', 'phone' => '0912 345 678',
        'password' => 'matkhau123', 'password_confirmation' => 'matkhau123',
    ])->assertRedirect(route('booking'));

    $this->assertAuthenticated();
    expect(User::firstWhere('email', 'c@example.com'))->phone->toBe('0912345678')->role->value->toBe('customer');
});

it('kiểm tra dữ liệu đăng ký phía server, báo lỗi tiếng Việt', function () {
    User::factory()->create(['email' => 'trung@example.com']);
    $this->from('/dang-ky')->post('/dang-ky', ['name' => '', 'email' => 'trung@example.com', 'phone' => '123', 'password' => 'abc', 'password_confirmation' => 'xyz'])
        ->assertRedirect('/dang-ky')
        ->assertSessionHasErrors(['name', 'email', 'phone', 'password']);
    $this->assertGuest();
});

it('đăng nhập: khách vào trang đặt lịch, nhân viên vào trang quản lý; sai mật khẩu bị từ chối', function () {
    $c = User::factory()->create(['email' => 'k@example.com']);
    $this->post('/dang-nhap', ['email' => 'k@example.com', 'password' => 'sai'])->assertSessionHasErrors('email');
    $this->post('/dang-nhap', ['email' => 'k@example.com', 'password' => 'password'])->assertRedirect(route('booking'));
    $this->post('/dang-xuat')->assertRedirect(route('home'));

    $s = staff();
    $this->post('/dang-nhap', ['email' => $s->email, 'password' => 'password'])->assertRedirect(route('admin.schedule'));
});

it('khóa đăng nhập sau 5 lần sai', function () {
    User::factory()->create(['email' => 'x@example.com']);
    foreach (range(1, 5) as $i) {
        $this->post('/dang-nhap', ['email' => 'x@example.com', 'password' => 'sai']);
    }
    $this->post('/dang-nhap', ['email' => 'x@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('form POST thiếu CSRF token bị từ chối (419)', function () {
    $this->withMiddleware(ValidateCsrfToken::class);
    // Laravel tắt kiểm tra CSRF khi chạy test, nên bật lại thủ công để kiểm tra
    $this->app->detectEnvironment(fn () => 'production');
    $this->post('/dang-nhap', ['email' => 'a@b.c', 'password' => 'x'])->assertStatus(419);
});
