<?php

use App\Services\WeatherService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => Cache::flush());

function openMeteo(int $code = 61): array
{
    return [
        'current' => ['temperature_2m' => 27.6, 'weather_code' => $code],
        'daily' => ['temperature_2m_min' => [24.2], 'temperature_2m_max' => [31.4], 'precipitation_probability_max' => [70]],
    ];
}

it('đọc dữ liệu Open-Meteo và đổi mã thời tiết sang tiếng Việt', function () {
    Http::fake(['api.open-meteo.com/*' => Http::response(openMeteo(61))]);

    expect(app(WeatherService::class)->today())->toMatchArray([
        'temperature' => 28.0, 'min' => 24.0, 'max' => 31.0, 'rain_chance' => 70, 'label' => 'Mưa nhẹ',
    ]);
    Http::assertSent(fn ($req) => $req['timezone'] === 'Asia/Ho_Chi_Minh' && $req['forecast_days'] === 1);
});

it('gọi API một lần rồi dùng cache cho các lần sau', function () {
    Http::fake(['api.open-meteo.com/*' => Http::response(openMeteo(0))]);
    $service = app(WeatherService::class);

    $service->today();
    $service->today();

    Http::assertSentCount(1);
});

it('trả null khi API lỗi và không gọi lại liên tục', function () {
    Http::fake(['api.open-meteo.com/*' => Http::response('lỗi', 500)]);
    $service = app(WeatherService::class);

    expect($service->today())->toBeNull()
        ->and($service->today())->toBeNull();
    Http::assertSentCount(2); // retry(2) = tối đa 2 lần gọi, lần sau lấy từ cache
});

it('mã thời tiết lạ vẫn hiển thị được', function () {
    Http::fake(['api.open-meteo.com/*' => Http::response(openMeteo(7))]);
    expect(app(WeatherService::class)->today()['label'])->toBe('Không rõ');
});
