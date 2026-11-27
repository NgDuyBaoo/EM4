<?php

use App\Models\Message;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => seedBranches());

it('trang chủ hiển thị chi nhánh, phòng và thời tiết Huế', function () {
    Http::fake(['api.open-meteo.com/*' => Http::response([
        'current' => ['temperature_2m' => 25, 'weather_code' => 0],
        'daily' => ['temperature_2m_max' => [29], 'temperature_2m_min' => [22], 'precipitation_probability_max' => [10]],
    ])]);
    $this->get('/')->assertOk()
        ->assertSee('36 Nguyễn Sinh Cung')->assertSee('Phòng 1 · Vintage')
        ->assertSee('Huế hôm nay: Trời quang, 25°C');
});

it('trang chủ vẫn chạy khi API thời tiết lỗi', function () {
    Http::fake(['*' => Http::response(null, 500)]);
    $this->get('/')->assertOk()->assertDontSee('Huế hôm nay');
});

it('gửi lời nhắn qua form liên hệ có kiểm tra dữ liệu', function () {
    $this->get('/lien-he')->assertOk()->assertSee('name="_token"', false);
    $this->from('/lien-he')->post('/lien-he', ['body' => ''])->assertSessionHasErrors(['contact', 'body']);
    $this->post('/lien-he', ['contact' => '0912345678', 'body' => 'Chụp nhóm 5 người được không?', 'subject' => 'Khác'])
        ->assertRedirect()->assertSessionHas('status');
    expect(Message::count())->toBe(1);
});

it('khách chưa đăng nhập bị chuyển tới trang đăng nhập khi đặt lịch', function () {
    $this->get('/dat-lich')->assertRedirect(route('login'));
});

it('khách không vào được trang quản lý, nhân viên không vào được trang phòng', function () {
    $this->actingAs(customer())->get('/admin')->assertForbidden();
    $this->actingAs(staff())->get('/admin')->assertOk();
    $this->actingAs(staff())->get('/admin/phong')->assertForbidden();
    $this->actingAs(owner())->get('/admin/phong')->assertOk();
});

it('trang 404 tiếng Việt', function () {
    $this->get('/khong-co-trang-nay')->assertNotFound()->assertSee('Không tìm thấy trang');
});
