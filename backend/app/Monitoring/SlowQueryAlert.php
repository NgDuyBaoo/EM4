<?php

namespace App\Monitoring;

use App\Jobs\SendSlowQueryAlert;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cảnh báo truy vấn chậm: ngoài việc ghi log, báo cho chủ tiệm (email, Slack nếu có cấu hình).
 * Cùng một câu SQL chỉ báo 1 lần trong `throttle_minutes` phút để không gửi dồn dập.
 * Việc gửi chạy trong queue nên request đang chậm không bị chậm thêm.
 */
class SlowQueryAlert
{
    /** Chặn vòng lặp: truy vấn phát sinh trong lúc gửi cảnh báo không tự gây cảnh báo mới. */
    private static bool $sending = false;

    /** DROP/CREATE/ALTER/TRUNCATE (chạy migration) chậm là bình thường, không báo. */
    public static function isSchemaChange(string $sql): bool
    {
        return (bool) preg_match('/^\s*(drop|create|alter|truncate|rename)\s/i', $sql);
    }

    public static function report(string $kind, string $sql, float $ms, ?string $url = null): void
    {
        $cfg = config('ngoi.slow_query_alert');
        if (self::$sending || ! $cfg['enabled'] || self::isSchemaChange($sql)) {
            return;
        }

        $key = 'slow-query-alert:'.md5($kind.'|'.$sql);
        self::$sending = true;
        try {
            if (Cache::add($key, true, now()->addMinutes((int) $cfg['throttle_minutes']))) {
                SendSlowQueryAlert::dispatch($kind, $sql, $ms, $url);
            }
        } catch (Throwable $e) {
            // Gửi cảnh báo lỗi (cache/queue chưa sẵn sàng, ví dụ lúc migrate:fresh vừa xóa bảng cache)
            // thì chỉ ghi log, không được làm hỏng truy vấn đang chạy.
            Log::channel('slow-query')->error('Không gửi được cảnh báo truy vấn chậm', ['error' => $e->getMessage()]);
        } finally {
            self::$sending = false;
        }
    }
}
