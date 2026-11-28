<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Http;

function fakeProviders(array $override = []): void
{
    Http::fake($override + [
        'api.open-meteo.com/*' => Http::response([
            'current' => ['temperature_2m' => 26.4, 'weather_code' => 2],
            'daily' => ['temperature_2m_min' => [23], 'temperature_2m_max' => [30], 'precipitation_probability_max' => [40]],
        ]),
        'api.vietqr.io/v2/banks' => Http::response(['code' => '00', 'desc' => 'ok', 'data' => [
            ['name' => 'Ngân hàng TMCP Ngoại Thương Việt Nam', 'code' => 'VCB', 'bin' => '970436', 'shortName' => 'Vietcombank', 'logo' => null],
        ]]),
        'img.vietqr.io/*' => Http::response('PNG', 200, ['Content-Type' => 'image/png']),
        'api.vietqr.io/v2/generate' => Http::response(['code' => '00', 'data' => ['qrDataURL' => 'data:image/png;base64,AAA']]),
    ]);
}

it('gọi từng API ngoài, in bảng kết quả và ghi file JSON', function () {
    fakeProviders();
    $path = tempnam(sys_get_temp_dir(), 'int');
    config(['ngoi.vietqr.bank_bin' => '970436']);

    $this->artisan('ngoi:check-integrations', ['--json' => $path])
        ->expectsOutputToContain('Có mây, 26°C')
        ->expectsOutputToContain('ngân hàng của tiệm: Vietcombank')
        ->expectsOutputToContain('Bỏ qua: chưa đặt VIETQR_CLIENT_ID')
        ->assertSuccessful();

    $report = json_decode(file_get_contents($path), true);
    expect(collect($report['results'])->pluck('ok')->all())->toBe([true, true, true, null]);
});

it('có khóa API thì kiểm cả VietQR generate', function () {
    fakeProviders();
    config(['services.vietqr.client_id' => 'cid', 'services.vietqr.api_key' => 'key']);

    $this->artisan('ngoi:check-integrations')->expectsOutputToContain('data URL')->assertSuccessful();
    Http::assertSent(fn ($req) => str_contains($req->url(), 'v2/generate') && $req->hasHeader('x-api-key', 'key'));
});

it('--strict trả mã lỗi khi có API không chạy', function () {
    fakeProviders(['api.open-meteo.com/*' => Http::response('lỗi', 500)]);

    $this->artisan('ngoi:check-integrations', ['--strict' => true])->assertFailed();
});

it('được lên lịch chạy hằng ngày', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($e) => str_contains($e->command, 'ngoi:check-integrations'));

    expect($event?->expression)->toBe('0 7 * * *');
});
