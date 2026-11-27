<?php

use App\Enums\BookingStatus;
use App\Jobs\SendBookingConfirmation;
use App\Models\Booking;
use App\Support\SlotClock;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    freezeAt('10:00');
    [$this->b1, $this->b2] = seedBranches();
    $this->room = $this->b1->rooms->first();
    $this->customer = customer();
});

function book(array $override = []): array
{
    return array_merge([
        'branch_id' => test()->b1->id,
        'access_code' => todayCode(),
        'customer_name' => 'Nguyễn A',
        'customer_phone' => '0912 345 678',
        'items' => [['room_id' => test()->room->id, 'time' => '14:30'], ['room_id' => test()->room->id, 'time' => '14:40']],
    ], $override);
}

it('khách cần mã truy cập đúng để xem lịch trống', function () {
    Sanctum::actingAs($this->customer);
    $this->getJson("/api/branches/{$this->b1->id}/availability")->assertForbidden();
    $res = $this->withHeader('X-Access-Code', todayCode())->getJson("/api/branches/{$this->b1->id}/availability")->assertOk();

    expect($res->json('rooms'))->toHaveCount(3)
        ->and($res->json('slots'))->toHaveCount(84)
        ->and(collect($res->json('rooms.0.slots'))->firstWhere('time', '09:50')['state'])->toBe('past')
        ->and(collect($res->json('rooms.0.slots'))->firstWhere('time', '10:10')['state'])->toBe('free');
});

it('đặt lịch thành công, lưu giá, gửi email xác nhận qua queue', function () {
    Queue::fake();
    Sanctum::actingAs($this->customer);
    $res = $this->postJson('/api/bookings', book())->assertCreated();

    expect($res->json('code'))->toMatch('/^NG[A-Z2-9]{6}$/')
        ->and($res->json('total'))->toBe(100000)
        ->and(Booking::where('user_id', $this->customer->id)->count())->toBe(2);
    $this->assertDatabaseHas('bookings', ['customer_phone' => '0912345678', 'slot_time' => '14:30:00']);
    Queue::assertPushed(SendBookingConfirmation::class, fn ($job) => $job->bookingCode === $res->json('code'));
});

it('sai mã truy cập thì không đặt được', function () {
    Sanctum::actingAs($this->customer);
    $this->postJson('/api/bookings', book(['access_code' => '000000']))->assertUnprocessable()->assertJsonPath('code', 'INVALID_ACCESS_CODE');
});

it('chặn đặt trùng slot, slot đã hủy được đặt lại (BR-06)', function () {
    Sanctum::actingAs($this->customer);
    $this->postJson('/api/bookings', book())->assertCreated();
    $this->postJson('/api/bookings', book(['items' => [['room_id' => $this->room->id, 'time' => '14:40']]]))
        ->assertConflict()->assertJsonPath('code', 'SLOT_TAKEN');

    Booking::where('slot_time', '14:40:00')->first()->update(['status' => BookingStatus::Cancelled]);
    $this->postJson('/api/bookings', book(['items' => [['room_id' => $this->room->id, 'time' => '14:40']]]))->assertCreated();
});

it('database chặn trùng kể cả khi bỏ qua kiểm tra ở tầng ứng dụng', function () {
    Booking::factory()->for($this->room)->at('15:00')->create(['branch_id' => $this->b1->id]);
    expect(fn () => Booking::factory()->for($this->room)->at('15:00')->create(['branch_id' => $this->b1->id]))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('từ chối giờ lệch lưới, giờ đã qua, phòng chi nhánh khác, quá số slot', function () {
    Sanctum::actingAs($this->customer);
    $other = $this->b2->rooms->first();
    $this->postJson('/api/bookings', book(['items' => [['room_id' => $this->room->id, 'time' => '14:35']]]))->assertUnprocessable()->assertJsonPath('code', 'INVALID_SLOT');
    $this->postJson('/api/bookings', book(['items' => [['room_id' => $this->room->id, 'time' => '09:00']]]))->assertConflict()->assertJsonPath('code', 'SLOT_PAST');
    $this->postJson('/api/bookings', book(['items' => [['room_id' => $other->id, 'time' => '16:00']]]))->assertUnprocessable()->assertJsonPath('code', 'INVALID_ROOM');
    $many = collect(range(0, 12))->map(fn ($i) => ['room_id' => $this->room->id, 'time' => SlotClock::time(16 * 60 + $i * 10)])->all();
    $this->postJson('/api/bookings', book(['items' => $many]))->assertUnprocessable()->assertJsonValidationErrors('items');
    $this->postJson('/api/bookings', book(['customer_phone' => '123']))->assertUnprocessable()->assertJsonValidationErrors('customer_phone');
});

it('chỉ đặt cho ngày hôm nay: sang ngày mới thì mã cũ hết hiệu lực (BR-01, BR-03)', function () {
    Sanctum::actingAs($this->customer);
    $code = todayCode();
    freezeAt('08:00', '2026-10-17');
    $this->postJson('/api/bookings', book(['access_code' => $code]))->assertUnprocessable();
    $res = $this->postJson('/api/bookings', book(['access_code' => todayCode()]))->assertCreated();
    expect($res->json('data.0.date'))->toBe('2026-10-17');
});

it('nhân viên: check-in → hoàn thành, không cho chuyển sai', function () {
    $b = Booking::factory()->for($this->room)->at('14:30')->create(['branch_id' => $this->b1->id]);
    Sanctum::actingAs(staff(), ['customer', 'staff']);

    $this->patchJson("/api/bookings/{$b->id}", ['action' => 'complete'])->assertConflict()->assertJsonPath('code', 'INVALID_TRANSITION');
    $this->patchJson("/api/bookings/{$b->id}", ['action' => 'checkin'])->assertOk()->assertJsonPath('data.status', 'checked_in');
    $this->patchJson("/api/bookings/{$b->id}", ['action' => 'complete'])->assertOk()->assertJsonPath('data.status', 'completed');
    expect($b->fresh()->completed_at)->not->toBeNull();
});

it('nhân viên đặt tại quầy (walk-in) được slot đang diễn ra, không cần mã', function () {
    freezeAt('16:05');
    Sanctum::actingAs(staff(), ['customer', 'staff']);
    $this->postJson('/api/bookings', book(['access_code' => null, 'items' => [['room_id' => $this->room->id, 'time' => '16:00']]]))
        ->assertCreated()->assertJsonPath('data.0.source', 'walkin');
});

it('khách chỉ xem và hủy booking của mình, trước giờ chụp', function () {
    $mine = Booking::factory()->for($this->room)->at('14:30')->create(['branch_id' => $this->b1->id, 'user_id' => $this->customer->id]);
    $other = Booking::factory()->for($this->room)->at('15:30')->create(['branch_id' => $this->b1->id]);
    Sanctum::actingAs($this->customer);

    $this->getJson('/api/bookings')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson("/api/bookings/{$other->id}")->assertForbidden();
    $this->patchJson("/api/bookings/{$mine->id}", ['action' => 'checkin'])->assertUnprocessable();
    $this->patchJson("/api/bookings/{$mine->id}", ['customer_name' => 'Đổi tên'])->assertUnprocessable();
    $this->patchJson("/api/bookings/{$mine->id}", ['action' => 'cancel'])->assertOk()->assertJsonPath('data.status', 'cancelled');

    freezeAt('16:00');
    $late = Booking::factory()->for($this->room)->at('15:50')->create(['branch_id' => $this->b1->id, 'user_id' => $this->customer->id]);
    $this->patchJson("/api/bookings/{$late->id}", ['action' => 'cancel'])->assertConflict()->assertJsonPath('code', 'NOT_CANCELLABLE');
});

it('nhân viên lọc booking theo trạng thái, tìm kiếm, phân trang; không N+1', function () {
    Booking::factory()->count(15)->sequence(fn ($s) => ['slot_time' => SlotClock::time(11 * 60 + $s->index * 10).':00'])
        ->for($this->room)->create(['branch_id' => $this->b1->id]);
    Booking::factory()->for($this->room)->at('18:00')->status(BookingStatus::Cancelled)->create(['branch_id' => $this->b1->id, 'customer_name' => 'Khách Zeta']);
    Sanctum::actingAs(staff(), ['staff']);

    DB::enableQueryLog();
    $this->getJson('/api/bookings?per_page=10')->assertOk()->assertJsonCount(10, 'data')->assertJsonPath('meta.total', 16);
    expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(6);
    $this->getJson('/api/bookings?status=cancelled')->assertJsonCount(1, 'data');
    $this->getJson('/api/bookings?q=Zeta')->assertJsonCount(1, 'data');
});

it('chỉ chủ cửa hàng được xóa hẳn booking', function () {
    $b = Booking::factory()->for($this->room)->create(['branch_id' => $this->b1->id]);
    Sanctum::actingAs(staff(), ['staff']);
    $this->deleteJson("/api/bookings/{$b->id}")->assertForbidden();
    Sanctum::actingAs(owner(), ['staff']);
    $this->deleteJson("/api/bookings/{$b->id}")->assertNoContent();
    $this->assertModelMissing($b);
});
