<?php

use App\Models\Room;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake(['*' => Http::response(null, 500)]); // thời tiết lỗi: trang vẫn chạy
    seedBranches();
});

it('API danh mục công khai có Cache-Control public và ETag', function () {
    $res = $this->getJson('/api/branches')->assertOk();

    expect($res->headers->get('Cache-Control'))->toContain('public')->toContain('max-age=300')
        ->and($res->headers->get('ETag'))->not->toBeEmpty();
});

it('gửi lại If-None-Match đúng ETag thì nhận 304, không tải lại dữ liệu', function () {
    $etag = $this->getJson('/api/rooms')->headers->get('ETag');

    $this->getJson('/api/rooms', ['If-None-Match' => $etag])->assertStatus(304)->assertContent('');
});

it('dữ liệu đổi thì ETag đổi', function () {
    $before = $this->getJson('/api/rooms')->headers->get('ETag');
    Room::query()->first()->update(['price_per_slot' => 70000]);

    expect($this->getJson('/api/rooms')->headers->get('ETag'))->not->toBe($before);
});

it('API cần đăng nhập trả Cache-Control no-store', function () {
    $token = customer()->createToken('t', ['customer'])->plainTextToken;

    $res = $this->withToken($token)->getJson('/api/auth/me')->assertOk();

    expect($res->headers->get('Cache-Control'))->toContain('no-store')->toContain('private');
});

it('trang chủ được cache toàn trang cho khách chưa đăng nhập', function () {
    $this->get('/')->assertOk()->assertHeader('X-Page-Cache', 'MISS');
    $this->get('/')->assertOk()->assertHeader('X-Page-Cache', 'HIT')->assertSee('Phòng 1');
});

it('trang lấy từ cache vẫn có CSRF token của phiên hiện tại, gửi form liên hệ được', function () {
    $this->get('/lien-he')->assertHeader('X-Page-Cache', 'MISS');

    $this->startSession();
    $res = $this->get('/lien-he')->assertHeader('X-Page-Cache', 'HIT');

    expect($res->getContent())->toContain(session()->token())->not->toContain('__NGOI_CSRF_TOKEN__');
});

it('người đã đăng nhập không nhận trang cache', function () {
    $this->get('/');
    $this->actingAs(customer())->get('/')->assertOk()->assertHeaderMissing('X-Page-Cache');
});

it('đổi danh mục phòng thì trang cache được dựng lại', function () {
    $this->get('/')->assertHeader('X-Page-Cache', 'MISS');
    Room::query()->first()->update(['name' => 'Phòng Hoàng Hôn']);

    $this->get('/')->assertHeader('X-Page-Cache', 'MISS')->assertSee('Phòng Hoàng Hôn');
});
