<?php

use App\Enums\BookingStatus;
use App\Livewire\Admin\Bookings;
use App\Livewire\Admin\Messages;
use App\Livewire\Admin\Schedule;
use App\Livewire\Admin\Settings;
use App\Models\Booking;
use App\Models\Message;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

beforeEach(function () {
    freezeAt('10:00');
    [$this->b1] = seedBranches();
    $this->room = $this->b1->rooms->first();
});

it('nhân viên dùng token không có quyền staff thì không đổi được trạng thái booking của người khác', function () {
    $booking = Booking::factory()->for($this->room)->at('14:00')->create(['branch_id' => $this->b1->id]);
    $token = staff()->createToken('t', ['customer'])->plainTextToken;

    $this->withToken($token)->patchJson("/api/bookings/{$booking->id}", ['action' => 'checkin'])->assertUnprocessable();

    expect($booking->fresh()->status)->toBe(BookingStatus::Booked);
});

it('nhân viên dùng token có quyền staff thì check-in được', function () {
    $booking = Booking::factory()->for($this->room)->at('14:00')->create(['branch_id' => $this->b1->id]);
    $token = staff()->createToken('t', ['staff'])->plainTextToken;

    $this->withToken($token)->patchJson("/api/bookings/{$booking->id}", ['action' => 'checkin'])->assertOk()
        ->assertJsonPath('data.status', 'checked_in');
});

it('khách gọi thẳng action của component quản trị thì bị chặn 403', function (string $component, string $method, array $args) {
    $booking = Booking::factory()->for($this->room)->at('14:00')->create(['branch_id' => $this->b1->id]);
    $args = array_map(fn ($a) => $a === ':id' ? $booking->id : $a, $args);

    Livewire::actingAs(customer())->test($component)->call($method, ...$args)->assertForbidden();

    expect($booking->fresh()->status)->toBe(BookingStatus::Booked);
})->with([
    'Bookings::act' => [Bookings::class, 'act', [':id', 'checkin']],
    'Schedule::act' => [Schedule::class, 'act', [':id', 'checkin']],
    'Schedule::openWalkin' => [Schedule::class, 'openWalkin', [1, '15:00']],
    'Settings::regenerate' => [Settings::class, 'regenerate', []],
]);

it('khách không đánh dấu được lời nhắn', function () {
    $msg = Message::factory()->create();

    Livewire::actingAs(customer())->test(Messages::class)->call('toggle', $msg->id)->assertForbidden();
});

it('log truy vấn chậm không chứa dữ liệu khách (bindings)', function () {
    config(['ngoi.slow_query_ms' => 0, 'ngoi.slow_query_alert.enabled' => false]);
    $logged = [];
    Log::shouldReceive('channel')->andReturn($log = Mockery::mock());
    $log->shouldReceive('warning')->andReturnUsing(function ($msg, $ctx) use (&$logged) {
        $logged[] = $ctx;
    });
    $log->shouldReceive('error', 'info');

    DB::select('select * from users where phone = ?', ['0912345678']);

    expect(json_encode($logged))->not->toContain('0912345678')
        ->and($logged[0]['bindings_count'])->toBe(1);
});

it('/api/health công khai chỉ trả ok, chủ cửa hàng xem được chi tiết', function () {
    $this->getJson('/api/health', ['REMOTE_ADDR' => '203.0.113.5'])
        ->assertOk()->assertExactJson(['ok' => true]);

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.5'])
        ->withToken(owner()->createToken('t', ['staff'])->plainTextToken)
        ->getJson('/api/health')->assertOk()->assertJsonPath('checks.database.ok', true);
});

it('seeder không tạo tài khoản mật khẩu mặc định ngoài local/test', function () {
    app()->detectEnvironment(fn () => 'production');
    putenv('SEED_ADMIN_PASSWORD');
    unset($_ENV['SEED_ADMIN_PASSWORD'], $_SERVER['SEED_ADMIN_PASSWORD']);

    expect(fn () => (new UserSeeder)->run())->toThrow(RuntimeException::class, 'SEED_ADMIN_PASSWORD');
});

it('production có SEED_ADMIN_PASSWORD thì chỉ tạo tài khoản chủ cửa hàng', function () {
    app()->detectEnvironment(fn () => 'production');
    $_ENV['SEED_ADMIN_PASSWORD'] = $_SERVER['SEED_ADMIN_PASSWORD'] = 'Mat-khau-manh-123';
    putenv('SEED_ADMIN_PASSWORD=Mat-khau-manh-123');

    (new UserSeeder)->run();

    expect(User::where('email', 'admin@ngoi.vn')->exists())->toBeTrue()
        ->and(User::where('email', 'nhanvien@ngoi.vn')->exists())->toBeFalse();

    putenv('SEED_ADMIN_PASSWORD');
    unset($_ENV['SEED_ADMIN_PASSWORD'], $_SERVER['SEED_ADMIN_PASSWORD']);
});
