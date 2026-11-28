<?php

namespace App\Services\Zalo;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gửi tin Zalo ZNS (Zalo Notification Service) qua Official Account của tiệm.
 * Tài liệu: https://developers.zalo.me/docs/zalo-notification-service
 *
 * Driver (ZALO_DRIVER):
 *  - log: chỉ ghi nội dung tin vào storage/logs/zalo-*.log (mặc định, dùng khi chưa có OA)
 *  - zns: gọi API thật của Zalo
 *
 * Access token của OA sống khoảng 25 giờ, refresh token dùng được 1 lần và sống 3 tháng.
 * Mỗi lần làm mới, Zalo trả cặp token mới; cặp này được lưu vào cache (store bền như database/redis)
 * để lần sau dùng tiếp. ZALO_REFRESH_TOKEN trong .env chỉ là giá trị khởi đầu.
 */
class ZaloZns
{
    public const ACCESS_KEY = 'zalo:access_token';

    public const REFRESH_KEY = 'zalo:refresh_token';

    /** Mã lỗi Zalo khi access token hết hạn hoặc không hợp lệ. */
    private const TOKEN_ERRORS = [-124, -216];

    /**
     * Gửi 1 tin theo mẫu đã duyệt. Trả về msg_id của Zalo (driver log trả null).
     *
     * @param  array<string, string>  $data  tham số của mẫu tin, đúng tên tham số đã đăng ký
     */
    public function send(string $phone, string $templateId, array $data, string $trackingId): ?string
    {
        $body = [
            'phone' => self::toInternational($phone),
            'template_id' => $templateId,
            'template_data' => $data,
            'tracking_id' => substr($trackingId, 0, 48),
        ];
        if (config('services.zalo.mode') === 'development') {
            $body['mode'] = 'development'; // app chưa duyệt: chỉ gửi được tới quản trị viên OA/app
        }

        if (config('services.zalo.driver') !== 'zns') {
            Log::channel('zalo')->info('ZNS (driver log, không gửi thật)', $body);

            return null;
        }

        $res = $this->post($body, $this->accessToken());
        if (in_array($res['error'] ?? 0, self::TOKEN_ERRORS, true)) {
            $res = $this->post($body, $this->refresh());
        }
        if (($res['error'] ?? -1) !== 0) {
            throw new ZaloException((string) ($res['message'] ?? 'không rõ lỗi'), $res['error'] ?? 'http');
        }

        Log::channel('zalo')->info('Đã gửi ZNS', ['tracking_id' => $body['tracking_id'], 'template_id' => $templateId, 'msg_id' => $res['data']['msg_id'] ?? null]);

        return $res['data']['msg_id'] ?? null;
    }

    /** 0912345678 → 84912345678 (định dạng Zalo yêu cầu). */
    public static function toInternational(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        return str_starts_with($digits, '0') ? '84'.substr($digits, 1) : $digits;
    }

    private function post(array $body, string $token): array
    {
        return Http::timeout(10)
            ->withHeaders(['access_token' => $token])
            ->post(config('services.zalo.send_url'), $body)
            ->throw()
            ->json() ?? [];
    }

    private function accessToken(): string
    {
        return Cache::get(self::ACCESS_KEY) ?: (string) config('services.zalo.access_token') ?: $this->refresh();
    }

    /** Đổi refresh token lấy cặp token mới. Có khóa để 2 worker không cùng dùng 1 refresh token (dùng 1 lần là hết). */
    public function refresh(): string
    {
        return Cache::lock('zalo:refresh-lock', 30)->block(20, function () {
            $refresh = Cache::get(self::REFRESH_KEY) ?: config('services.zalo.refresh_token');
            if (! $refresh || ! config('services.zalo.app_id') || ! config('services.zalo.secret_key')) {
                throw new ZaloException('Thiếu ZALO_APP_ID / ZALO_SECRET_KEY / ZALO_REFRESH_TOKEN', 'config');
            }

            $res = Http::asForm()->timeout(10)
                ->withHeaders(['secret_key' => config('services.zalo.secret_key')])
                ->post(config('services.zalo.token_url'), [
                    'app_id' => config('services.zalo.app_id'),
                    'refresh_token' => $refresh,
                    'grant_type' => 'refresh_token',
                ])->throw()->json() ?? [];

            if (empty($res['access_token'])) {
                throw new ZaloException((string) ($res['error_description'] ?? $res['error_name'] ?? 'không làm mới được token'), $res['error'] ?? 'token');
            }

            $ttl = max(60, (int) ($res['expires_in'] ?? 3600) - 300);
            Cache::put(self::ACCESS_KEY, $res['access_token'], $ttl);
            if (! empty($res['refresh_token'])) {
                Cache::forever(self::REFRESH_KEY, $res['refresh_token']);
            }
            Log::channel('zalo')->info('Đã làm mới access token Zalo OA', ['expires_in' => $res['expires_in'] ?? null]);

            return $res['access_token'];
        });
    }
}
