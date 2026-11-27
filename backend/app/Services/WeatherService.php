<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tích hợp API bên ngoài: dự báo thời tiết Huế hôm nay từ Open-Meteo.
 * Hiển thị trên trang chủ và trang đặt lịch để khách chọn giờ chụp (trời mưa thì đến sớm…).
 * Kết quả cache 30 phút; API lỗi thì trả null và trang vẫn chạy bình thường.
 */
class WeatherService
{
    private const CODES = [
        0 => ['Trời quang', '☀️'], 1 => ['Ít mây', '🌤️'], 2 => ['Có mây', '⛅'], 3 => ['Nhiều mây', '☁️'],
        45 => ['Sương mù', '🌫️'], 48 => ['Sương mù', '🌫️'],
        51 => ['Mưa phùn nhẹ', '🌦️'], 53 => ['Mưa phùn', '🌦️'], 55 => ['Mưa phùn dày', '🌧️'],
        61 => ['Mưa nhẹ', '🌦️'], 63 => ['Mưa vừa', '🌧️'], 65 => ['Mưa to', '🌧️'],
        80 => ['Mưa rào nhẹ', '🌦️'], 81 => ['Mưa rào', '🌧️'], 82 => ['Mưa rào lớn', '⛈️'],
        95 => ['Dông', '⛈️'], 96 => ['Dông kèm mưa đá', '⛈️'], 99 => ['Dông kèm mưa đá', '⛈️'],
    ];

    public function today(): ?array
    {
        $cfg = config('ngoi.weather');
        $key = 'weather:hue:'.now()->format('Y-m-d-H');

        $cached = Cache::get($key);
        if ($cached !== null) {
            return $cached ?: null;
        }

        try {
            $res = Http::timeout(4)->retry(2, 200)->get($cfg['url'], [
                'latitude' => $cfg['latitude'],
                'longitude' => $cfg['longitude'],
                'current' => 'temperature_2m,weather_code',
                'daily' => 'temperature_2m_max,temperature_2m_min,precipitation_probability_max',
                'timezone' => 'Asia/Ho_Chi_Minh',
                'forecast_days' => 1,
            ])->throw()->json();

            $code = (int) data_get($res, 'current.weather_code', 0);
            [$label, $icon] = self::CODES[$code] ?? ['Không rõ', '🌡️'];
            $data = [
                'temperature' => round((float) data_get($res, 'current.temperature_2m')),
                'min' => round((float) data_get($res, 'daily.temperature_2m_min.0')),
                'max' => round((float) data_get($res, 'daily.temperature_2m_max.0')),
                'rain_chance' => (int) data_get($res, 'daily.precipitation_probability_max.0', 0),
                'label' => $label,
                'icon' => $icon,
            ];
            Cache::put($key, $data, now()->addMinutes($cfg['cache_minutes']));

            return $data;
        } catch (Throwable $e) {
            Log::warning('Không lấy được thời tiết từ Open-Meteo', ['error' => $e->getMessage()]);
            Cache::put($key, [], now()->addMinutes(5)); // tránh gọi lại liên tục khi API lỗi

            return null;
        }
    }
}
