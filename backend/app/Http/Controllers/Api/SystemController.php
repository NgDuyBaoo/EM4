<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Throwable;

/**
 * @tags Hệ thống
 */
class SystemController extends Controller
{
    /**
     * Kiểm tra sức khỏe hệ thống: database, cache, hàng đợi. Dùng cho Docker healthcheck / uptime monitor.
     *
     * Công khai chỉ trả `ok` (200 hoặc 503). Chi tiết từng thành phần chỉ trả cho chủ cửa hàng (token Sanctum)
     * hoặc request từ chính máy chủ (healthcheck nội bộ).
     *
     * @unauthenticated
     */
    public function health(Request $request): JsonResponse
    {
        $checks = [];
        $started = microtime(true);
        try {
            DB::select('select 1');
            $checks['database'] = ['ok' => true, 'ms' => round((microtime(true) - $started) * 1000, 1)];
        } catch (Throwable $e) {
            $checks['database'] = ['ok' => false, 'error' => class_basename($e)];
        }
        try {
            // Dùng chuỗi ngẫu nhiên: Redis trả số dưới dạng chuỗi ("1"), so sánh với số 1 sẽ luôn sai.
            $token = Str::random(16);
            Cache::put('health:ping', $token, 10);
            $checks['cache'] = ['ok' => Cache::get('health:ping') === $token];
        } catch (Throwable $e) {
            $checks['cache'] = ['ok' => false, 'error' => class_basename($e)];
        }
        try {
            $size = Queue::size();
            $checks['queue'] = ['ok' => $size < 1000, 'pending_jobs' => $size];
        } catch (Throwable $e) {
            $checks['queue'] = ['ok' => false, 'error' => class_basename($e)];
        }
        $ok = collect($checks)->every(fn ($c) => $c['ok']);

        $detail = $request->user('sanctum')?->isOwner() || in_array($request->ip(), ['127.0.0.1', '::1'], true);

        return response()->json(
            $detail ? ['ok' => $ok, 'time' => now()->toIso8601String(), 'checks' => $checks] : ['ok' => $ok],
            $ok ? 200 : 503,
        );
    }

    /**
     * Thời tiết Huế hôm nay (từ Open-Meteo, cache 30 phút).
     *
     * @unauthenticated
     */
    public function weather(WeatherService $weather): JsonResponse
    {
        return response()->json(['data' => $weather->today()]);
    }
}
