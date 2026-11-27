<?php

use App\Enums\BookingStatus;
use App\Livewire\Customer\BookingWizard;
use App\Livewire\Customer\MyBookings;
use App\Models\Booking;
use App\Notifications\BookingConfirmed;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Http::fake([
        // API danh sách ngân hàng của VietQR (hiện cạnh mã QR ở bước cuối)
        'api.vietqr.io/*' => Http::response(['code' => '00', 'desc' => 'ok', 'data' => [
            ['name' => 'Ngân hàng TMCP Ngoại Thương Việt Nam', 'code' => 'VCB', 'bin' => '970436', 'shortName' => 'Vietcombank', 'logo' => 'https://api.vietqr.io/img/VCB.png'],
        ]]),
        '*' => Http::response(),
    ]);
    freezeAt('10:00');
    [$this->b1] = seedBranches();
    $this->room = $this->b1->rooms->first();
    $this->user = customer();
    $this->actingAs($this->user);
});

it('đi hết 6 bước và đặt lịch thành công, gửi email xác nhận', function () {
    Notification::fake();
    Livewire::test(BookingWizard::class)
        ->assertSet('step', 1)
        ->set('code', '000000')->call('verifyCode')->assertHasErrors('code')->assertSet('step', 1)
        ->set('code', todayCode())->call('verifyCode')->assertSet('step', 2)
        ->call('chooseBranch', $this->b1->id)->call('go', 3)->assertSet('step', 3)
        ->call('toggleRoom', $this->room->id)->call('go', 4)->assertSet('step', 4)
        ->assertSee('14:30')
        ->call('toggleSlot', $this->room->id, '14:30')
        ->call('toggleSlot', $this->room->id, '09:00') // đã qua: không chọn được
        ->assertSet('selected', ["{$this->room->id}|14:30"])
        ->call('go', 5)->assertSet('step', 5)->assertSee('50.000đ')
        ->set('phone', '0912345678')
        ->call('confirm')
        ->assertSet('step', 6)
        ->assertSee('Đặt lịch thành công')
        ->assertSee('img.vietqr.io', false);

    $b = Booking::sole();
    expect($b->user_id)->toBe($this->user->id)->and($b->time())->toBe('14:30');
    Notification::assertSentTo($this->user, BookingConfirmed::class);
});

it('trang đặt thành công hiện ngân hàng nhận tiền lấy từ API VietQR', function () {
    Notification::fake();
    config(['ngoi.vietqr.bank_bin' => '970436', 'ngoi.vietqr.account' => '0123456789']);

    Livewire::test(BookingWizard::class)
        ->set('code', todayCode())->call('verifyCode')
        ->call('chooseBranch', $this->b1->id)->call('go', 3)
        ->call('toggleRoom', $this->room->id)->call('go', 4)
        ->call('toggleSlot', $this->room->id, '14:30')->call('go', 5)
        ->set('phone', '0912345678')->call('confirm')
        ->assertSet('step', 6)
        ->assertSee('Vietcombank · 0123456789')
        ->assertSee('https://api.vietqr.io/img/VCB.png', false);
});

it('nhớ mã đã nhập trong phiên: lần sau bắt đầu từ bước 2', function () {
    session(['access_ok' => '2026-10-16']);
    Livewire::test(BookingWizard::class)->assertSet('step', 2);
});

it('không cho nhảy bước khi chưa chọn đủ', function () {
    session(['access_ok' => '2026-10-16']);
    Livewire::test(BookingWizard::class)->call('go', 5)->assertSet('step', 2);
});

it('slot vừa bị người khác đặt: quay lại bước chọn giờ và báo rõ', function () {
    session(['access_ok' => '2026-10-16']);
    $c = Livewire::test(BookingWizard::class)
        ->call('chooseBranch', $this->b1->id)->call('toggleRoom', $this->room->id)->call('go', 4)
        ->call('toggleSlot', $this->room->id, '15:00')->call('go', 5);

    Booking::factory()->for($this->room)->at('15:00')->create(['branch_id' => $this->b1->id]); // người khác đặt trước

    $c->call('confirm')->assertSet('step', 4)->assertSet('selected', [])->assertSee('vừa có người đặt');
});

it('lịch của tôi: chỉ thấy lịch của mình, hủy được trước giờ chụp', function () {
    $mine = Booking::factory()->for($this->room)->at('14:30')->create(['branch_id' => $this->b1->id, 'user_id' => $this->user->id]);
    Booking::factory()->for($this->room)->at('16:00')->create(['branch_id' => $this->b1->id, 'customer_name' => 'Người khác']);

    Livewire::test(MyBookings::class)
        ->assertSee($mine->booking_code)->assertDontSee('Người khác')
        ->call('cancel', $mine->id)->assertSee('Đã hủy lịch 14:30');
    expect($mine->fresh()->status)->toBe(BookingStatus::Cancelled);
});
