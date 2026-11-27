<?php

use App\Enums\BookingStatus;
use App\Livewire\Admin\Bookings;
use App\Livewire\Admin\Reports;
use App\Livewire\Admin\Rooms;
use App\Livewire\Admin\Schedule;
use App\Livewire\Admin\Settings;
use App\Models\Booking;
use App\Models\Room;
use Livewire\Livewire;

beforeEach(function () {
    freezeAt('14:05');
    [$this->b1] = seedBranches();
    $this->room = $this->b1->rooms->first();
});

it('lưới lịch hiển thị khách, check-in và hoàn thành từ popup', function () {
    $b = Booking::factory()->for($this->room)->at('15:00')->create(['branch_id' => $this->b1->id, 'customer_name' => 'Nguyễn Khách']);
    $this->actingAs(staff());

    Livewire::test(Schedule::class)
        ->assertSee('Nguyễn Khách')->assertSee(todayCode())
        ->set('detailId', $b->id)->assertSee($b->booking_code)
        ->call('act', $b->id, 'checkin')->assertSee('Đang chụp')
        ->call('act', $b->id, 'complete');
    expect($b->fresh()->status)->toBe(BookingStatus::Completed);
});

it('đặt tại quầy từ ô trống, kể cả slot đang diễn ra', function () {
    $this->actingAs(staff());
    Livewire::test(Schedule::class)
        ->call('openWalkin', $this->room->id, '14:00')
        ->set('walkName', '')->call('saveWalkin')->assertHasErrors('walkName')
        ->set('walkName', 'Khách quầy')->set('walkPhone', '0911222333')->call('saveWalkin')
        ->assertHasNoErrors()->assertSet('walkin', null);
    expect(Booking::sole())->source->value->toBe('walkin')->status->toBe(BookingStatus::Booked);
});

it('danh sách booking lọc theo trạng thái và tìm kiếm', function () {
    Booking::factory()->for($this->room)->at('15:00')->create(['branch_id' => $this->b1->id, 'customer_name' => 'An Bình']);
    Booking::factory()->for($this->room)->at('15:10')->status(BookingStatus::Cancelled)->create(['branch_id' => $this->b1->id, 'customer_name' => 'Chí Dũng']);
    $this->actingAs(staff());

    Livewire::test(Bookings::class)
        ->assertSee('An Bình')->assertSee('Chí Dũng')
        ->set('status', 'cancelled')->assertDontSee('An Bình')->assertSee('Chí Dũng')
        ->set('status', 'all')->set('search', 'Bình')->assertSee('An Bình')->assertDontSee('Chí Dũng');
});

it('thống kê tải lazy và tính doanh thu', function () {
    Booking::factory()->for($this->room)->at('09:00')->status(BookingStatus::Completed)->create(['branch_id' => $this->b1->id]);
    $this->actingAs(staff());
    Livewire::withoutLazyLoading()->test(Reports::class)->assertSee('50.000đ')->assertSee('Hiệu suất từng phòng')
        ->call('export')->assertFileDownloaded();
});

it('chủ cửa hàng thêm, sửa giá, xóa phòng; giá sai bị từ chối', function () {
    $this->actingAs(owner());
    Livewire::test(Rooms::class)
        ->call('create')->set('form.name', 'Phòng 4')->set('form.price_per_slot', 60500)->call('save')->assertHasErrors('form.price_per_slot')
        ->set('form.price_per_slot', 60000)->call('save')->assertHasNoErrors();
    $room = Room::firstWhere('name', 'Phòng 4');
    expect($room->price_per_slot)->toBe(60000);

    Livewire::test(Rooms::class)->call('delete', $room->id);
    $this->assertSoftDeleted($room);
});

it('nhân viên không sửa được phòng dù gọi thẳng action', function () {
    $this->actingAs(staff());
    Livewire::test(Rooms::class)->call('edit', $this->room->id)->set('form.price_per_slot', 1000)->call('save')->assertForbidden();
});

it('đổi mã truy cập và đổi mật khẩu', function () {
    $this->actingAs($u = staff());
    $old = todayCode();
    Livewire::test(Settings::class)->call('regenerate');
    expect(todayCode())->not->toBe($old);

    Livewire::test(Settings::class)
        ->set('currentPassword', 'sai')->set('password', 'moi12345')->set('password_confirmation', 'moi12345')
        ->call('changePassword')->assertHasErrors('currentPassword')
        ->set('currentPassword', 'password')->call('changePassword')->assertHasNoErrors();
    expect(Hash::check('moi12345', $u->fresh()->password))->toBeTrue();
});
