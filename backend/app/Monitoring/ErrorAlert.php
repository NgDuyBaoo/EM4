<?php

namespace App\Monitoring;

use App\Exceptions\BookingException;
use App\Jobs\SendErrorAlert;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Báo ngay cho chủ tiệm khi có lỗi nghiêm trọng (exception không xử lý → HTTP 500).
 * Lỗi nghiệp vụ, lỗi 4xx, lỗi validate không báo (Laravel cũng không report các lỗi này).
 * Cùng một lỗi (class + file + dòng) chỉ báo 1 lần trong throttle_minutes phút.
 */
class ErrorAlert
{
    private static bool $sending = false;

    public static function report(Throwable $e): void
    {
        $cfg = config('ngoi.error_alert');
        if (self::$sending || ! $cfg['enabled'] || ! self::isSevere($e)) {
            return;
        }

        self::$sending = true;
        try {
            $key = 'error-alert:'.md5(get_class($e).'|'.$e->getFile().'|'.$e->getLine());
            if (Cache::add($key, true, now()->addMinutes((int) $cfg['throttle_minutes']))) {
                $request = request();
                SendErrorAlert::dispatch(
                    get_class($e),
                    self::safeMessage($e),
                    Str::after($e->getFile(), base_path().DIRECTORY_SEPARATOR),
                    $e->getLine(),
                    $request->route() ? $request->method().' '.$request->path() : 'console/queue',
                );
            }
        } catch (Throwable) {
            // Gửi cảnh báo lỗi thì không được làm hỏng việc ghi log lỗi gốc.
        } finally {
            self::$sending = false;
        }
    }

    public static function isSevere(Throwable $e): bool
    {
        if ($e instanceof BookingException) {
            return false;
        }

        return ! $e instanceof HttpExceptionInterface || $e->getStatusCode() >= 500;
    }

    /** Không gửi dữ liệu khách qua email/Slack: lỗi SQL chỉ gửi câu lệnh có dấu ?, không kèm giá trị. */
    private static function safeMessage(Throwable $e): string
    {
        if ($e instanceof QueryException) {
            return 'Lỗi SQL: '.Str::limit($e->getSql(), 500);
        }

        return Str::limit($e->getMessage(), 500);
    }
}
