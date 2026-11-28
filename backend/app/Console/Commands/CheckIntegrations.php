<?php

namespace App\Console\Commands;

use App\Services\BankDirectory;
use App\Services\VietQr;
use App\Services\WeatherService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Gọi thật các API bên ngoài (không dùng cache) để kiểm tra tích hợp còn chạy:
 * Open-Meteo, VietQR banks, VietQR Quick Link, và VietQR generate nếu có khóa.
 * Kết quả in ra bảng, ghi vào log kênh "integrations" và file JSON (dùng làm bằng chứng trên CI).
 */
class CheckIntegrations extends Command
{
    protected $signature = 'ngoi:check-integrations {--json= : Ghi kết quả ra file JSON} {--strict : Trả mã lỗi nếu có API không chạy}';

    protected $description = 'Gọi thật các API bên ngoài (Open-Meteo, VietQR) và báo kết quả';

    public function handle(WeatherService $weather, BankDirectory $banks): int
    {
        Cache::forget('weather:hue:'.now()->format('Y-m-d-H'));
        Cache::forget('vietqr:banks');

        $results = [
            $this->check('Open-Meteo (thời tiết Huế)', config('ngoi.weather.url'), function () use ($weather) {
                $w = $weather->today() ?? throw new \RuntimeException('Không có dữ liệu');

                return "{$w['icon']} {$w['label']}, {$w['temperature']}°C ({$w['min']}–{$w['max']}°C), mưa {$w['rain_chance']}%";
            }),
            $this->check('VietQR – danh sách ngân hàng', config('ngoi.vietqr.banks_url'), function () use ($banks) {
                $all = $banks->all() ?: throw new \RuntimeException('Danh sách rỗng');
                $shop = $banks->find((string) config('ngoi.vietqr.bank_bin'));

                return count($all).' ngân hàng; ngân hàng của tiệm: '.($shop['short_name'] ?? 'không tìm thấy BIN');
            }),
            $this->check('VietQR – ảnh QR (Quick Link)', 'https://img.vietqr.io/image/…', function () {
                $res = Http::timeout(10)->get(VietQr::imageUrl(50000, 'NGCHECK1'))->throw();

                return $res->header('Content-Type').', '.strlen($res->body()).' byte';
            }),
            VietQr::hasApiKey()
                ? $this->check('VietQR – tạo QR có khóa API', config('services.vietqr.generate_url'), function () {
                    $url = VietQr::generate(50000, 'NGCHECK'.now()->format('His')) ?? throw new \RuntimeException('API không trả ảnh QR');

                    return 'data URL '.strlen($url).' ký tự';
                })
                : ['name' => 'VietQR – tạo QR có khóa API', 'url' => config('services.vietqr.generate_url'), 'ok' => null, 'ms' => 0, 'detail' => 'Bỏ qua: chưa đặt VIETQR_CLIENT_ID / VIETQR_API_KEY'],
        ];

        $this->table(['API', 'Kết quả', 'Thời gian', 'Chi tiết'], array_map(fn ($r) => [
            $r['name'], $r['ok'] === null ? 'BỎ QUA' : ($r['ok'] ? 'OK' : 'LỖI'), $r['ms'].' ms', $r['detail'],
        ], $results));

        $report = ['checked_at' => now()->toIso8601String(), 'results' => $results];
        Log::channel('integrations')->info('Kiểm tra API ngoài', $report);
        if ($path = $this->option('json')) {
            file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->info("Đã ghi kết quả vào {$path}");
        }

        $failed = collect($results)->where('ok', false)->count();

        return $this->option('strict') && $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function check(string $name, string $url, callable $fn): array
    {
        $start = microtime(true);
        try {
            $detail = $fn();
            $ok = true;
        } catch (Throwable $e) {
            $detail = $e->getMessage();
            $ok = false;
        }

        return ['name' => $name, 'url' => $url, 'ok' => $ok, 'ms' => (int) round((microtime(true) - $start) * 1000), 'detail' => $detail];
    }
}
