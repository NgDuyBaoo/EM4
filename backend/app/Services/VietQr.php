<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tích hợp VietQR: tạo mã QR chuyển khoản theo chuẩn NAPAS cho đúng số tiền và nội dung là mã booking.
 *
 * - Có VIETQR_CLIENT_ID / VIETQR_API_KEY: gọi API có xác thực POST https://api.vietqr.io/v2/generate
 *   (header x-client-id, x-api-key), nhận ảnh QR dạng data URL. Kết quả cache theo mã booking.
 * - Không có khóa hoặc API lỗi: dùng Quick Link https://img.vietqr.io (không cần khóa).
 */
class VietQr
{
    /** Nguồn ảnh QR để đặt vào thẻ <img>. */
    public static function src(int $amount, string $bookingCode): string
    {
        return self::generate($amount, $bookingCode) ?? self::imageUrl($amount, $bookingCode);
    }

    public static function hasApiKey(): bool
    {
        return filled(config('services.vietqr.client_id')) && filled(config('services.vietqr.api_key'));
    }

    /** Gọi API có xác thực. Trả data URL ảnh QR, hoặc null nếu chưa cấu hình khóa / API lỗi. */
    public static function generate(int $amount, string $bookingCode): ?string
    {
        if (! self::hasApiKey()) {
            return null;
        }

        return Cache::remember("vietqr:qr:{$bookingCode}:{$amount}", now()->addDay(), function () use ($amount, $bookingCode) {
            $cfg = config('ngoi.vietqr');
            try {
                $res = Http::timeout(5)->retry(2, 200)
                    ->withHeaders([
                        'x-client-id' => config('services.vietqr.client_id'),
                        'x-api-key' => config('services.vietqr.api_key'),
                    ])
                    ->post(config('services.vietqr.generate_url'), [
                        'accountNo' => $cfg['account'],
                        'accountName' => $cfg['account_name'],
                        'acqId' => (int) $cfg['bank_bin'],
                        'amount' => $amount,
                        'addInfo' => $bookingCode,
                        'format' => 'text',
                        'template' => 'compact2',
                    ])->throw()->json();

                $url = data_get($res, 'data.qrDataURL');
                if (data_get($res, 'code') !== '00' || ! is_string($url)) {
                    throw new \RuntimeException('VietQR trả mã '.data_get($res, 'code').': '.data_get($res, 'desc'));
                }

                return $url;
            } catch (Throwable $e) {
                Log::warning('Không tạo được QR qua API VietQR, dùng link nhanh', ['error' => $e->getMessage()]);

                return null;
            }
        });
    }

    /** Quick Link (không cần khóa): https://www.vietqr.io/danh-sach-api/link-tao-ma-nhanh */
    public static function imageUrl(int $amount, string $bookingCode): string
    {
        $cfg = config('ngoi.vietqr');

        return sprintf(
            'https://img.vietqr.io/image/%s-%s-compact2.png?%s',
            $cfg['bank_bin'],
            $cfg['account'],
            http_build_query(['amount' => $amount, 'addInfo' => $bookingCode, 'accountName' => $cfg['account_name']]),
        );
    }
}
