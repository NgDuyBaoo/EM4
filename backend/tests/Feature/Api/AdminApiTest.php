<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Message;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    freezeAt('20:00');
    [$this->b1] = seedBranches();
});

it('khách không vào được API quản lý', function () {
    Sanctum::actingAs(customer());
    $this->getJson('/api/admin/access-code')->assertForbidden();
});

it('token nhân viên thiếu quyền staff thì bị chặn', function () {
    Sanctum::actingAs(staff(), ['customer']);
    $this->getJson('/api/admin/access-code')->assertForbidden();
});

it('xem và đổi mã truy cập hôm nay', function () {
    Sanctum::actingAs(staff(), ['staff']);
    $code = $this->getJson('/api/admin/access-code')->assertOk()->assertJsonPath('date', '2026-10-16')->json('code');
    expect($code)->toMatch('/^\d{6}$/');
    $new = $this->postJson('/api/admin/access-code/regenerate')->assertOk()->json('code');
    expect(todayCode())->toBe($new);
});

it('báo cáo: lượt đặt không tính hủy, doanh thu chỉ tính hoàn thành', function () {
    $room = $this->b1->rooms->first();
    Booking::factory()->for($room)->at('09:00')->status(BookingStatus::Completed)->create(['branch_id' => $this->b1->id]);
    Booking::factory()->for($room)->at('09:10')->status(BookingStatus::Completed)->create(['branch_id' => $this->b1->id]);
    Booking::factory()->for($room)->at('09:20')->status(BookingStatus::Cancelled)->create(['branch_id' => $this->b1->id]);
    Booking::factory()->for($room)->at('21:00')->create(['branch_id' => $this->b1->id]);
    Sanctum::actingAs(staff(), ['staff']);

    $res = $this->getJson('/api/admin/reports/summary?from=2026-10-10&to=2026-10-16')->assertOk();
    expect($res->json('totals'))->toMatchArray(['bookings' => 3, 'completed' => 2, 'cancelled' => 1, 'revenue' => 100000])
        ->and($res->json('days'))->toHaveCount(7)
        ->and($res->json('rooms.0.bookings'))->toBe(3)
        ->and($res->json('branches.0.revenue'))->toBe(100000);
    $this->getJson('/api/admin/reports/summary?from=2026-10-16&to=2026-10-10')->assertUnprocessable();
});

it('nhân viên quản lý lời nhắn khách gửi', function () {
    $this->postJson('/api/messages', ['contact' => '0912345678', 'body' => 'Cho mình hỏi giá chụp nhóm'])->assertCreated();
    $this->postJson('/api/messages', ['body' => 'thiếu liên hệ'])->assertUnprocessable()->assertJsonValidationErrors('contact');
    $msg = Message::first();
    Sanctum::actingAs(staff(), ['staff']);
    $this->getJson('/api/admin/messages')->assertOk()->assertJsonCount(1, 'data');
    $this->patchJson("/api/admin/messages/{$msg->id}", ['read' => true])->assertOk();
    expect($msg->fresh()->read_at)->not->toBeNull();
    $this->deleteJson("/api/admin/messages/{$msg->id}")->assertNoContent();
});

it('health check báo trạng thái database, cache, queue', function () {
    $this->getJson('/api/health')->assertOk()->assertJsonPath('ok', true)->assertJsonPath('checks.database.ok', true);
    $this->get('/up')->assertOk();
});

it('lấy thời tiết Huế từ Open-Meteo và cache lại', function () {
    Http::fake(['api.open-meteo.com/*' => Http::response([
        'current' => ['temperature_2m' => 27.4, 'weather_code' => 61],
        'daily' => ['temperature_2m_max' => [30], 'temperature_2m_min' => [23], 'precipitation_probability_max' => [70]],
    ])]);
    $this->getJson('/api/weather')->assertOk()->assertJsonPath('data.label', 'Mưa nhẹ')->assertJsonPath('data.temperature', 27);
    $this->getJson('/api/weather')->assertOk();
    Http::assertSentCount(1);
});

it('API thời tiết lỗi thì trả null, không làm hỏng trang', function () {
    Http::fake(['api.open-meteo.com/*' => Http::response('lỗi', 500)]);
    $this->getJson('/api/weather')->assertOk()->assertJsonPath('data', null);
});
