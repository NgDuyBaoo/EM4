<?php

use App\Models\Booking;
use App\Models\Room;
use Laravel\Sanctum\Sanctum;

beforeEach(fn () => [$this->b1, $this->b2] = seedBranches());

it('liệt kê chi nhánh kèm phòng đang hoạt động (không N+1)', function () {
    Room::factory()->for($this->b1)->inactive()->create();
    DB::enableQueryLog();
    $this->getJson('/api/branches')->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.rooms_count', 3)
        ->assertJsonCount(3, 'data.0.rooms');
    expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(3);
});

it('chủ cửa hàng CRUD phòng đầy đủ', function () {
    Sanctum::actingAs(owner(), ['customer', 'staff']);

    $id = $this->postJson('/api/rooms', ['branch_id' => $this->b1->id, 'name' => 'Phòng 4', 'style' => 'Modern', 'price_per_slot' => 60000])
        ->assertCreated()->json('data.id');
    $this->getJson("/api/rooms/{$id}")->assertOk()->assertJsonPath('data.style', 'Modern');
    $this->putJson("/api/rooms/{$id}", ['price_per_slot' => 70000, 'is_active' => false])->assertOk()->assertJsonPath('data.price_per_slot', 70000);
    $this->getJson("/api/rooms?branch_id={$this->b1->id}")->assertJsonCount(3, 'data');
    $this->deleteJson("/api/rooms/{$id}")->assertNoContent();
    $this->assertSoftDeleted('rooms', ['id' => $id]);
});

it('giá phòng phải là bội số 1.000đ', function () {
    Sanctum::actingAs(owner());
    $this->postJson('/api/rooms', ['branch_id' => $this->b1->id, 'name' => 'X', 'price_per_slot' => 50500])
        ->assertUnprocessable()->assertJsonValidationErrors('price_per_slot');
});

it('nhân viên và khách không được sửa phòng', function () {
    $room = $this->b1->rooms->first();
    Sanctum::actingAs(staff(), ['staff']);
    $this->putJson("/api/rooms/{$room->id}", ['price_per_slot' => 1000])->assertForbidden();
    Sanctum::actingAs(customer());
    $this->deleteJson("/api/rooms/{$room->id}")->assertForbidden();
});

it('chủ cửa hàng CRUD chi nhánh, không xóa được chi nhánh đã có booking', function () {
    Sanctum::actingAs(owner());
    $id = $this->postJson('/api/branches', ['code' => 'b3', 'name' => 'Chi nhánh 3', 'address' => 'Huế', 'open_time' => '09:00', 'close_time' => '21:00'])
        ->assertCreated()->json('data.id');
    $this->putJson("/api/branches/{$id}", ['name' => 'Chi nhánh mới'])->assertOk()->assertJsonPath('data.name', 'Chi nhánh mới');
    $this->deleteJson("/api/branches/{$id}")->assertNoContent();

    Booking::factory()->for($this->b1->rooms->first())->create(['branch_id' => $this->b1->id]);
    $this->deleteJson("/api/branches/{$this->b1->id}")->assertConflict();
});
