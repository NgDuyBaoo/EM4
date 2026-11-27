<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Throwable;

/**
 * @tags Hệ thống
 */
class SystemController extends Controller
{
    /**
     * Kiểm tra sức khỏe hệ thống: database, cache, hàng đợi. Dùng cho Docker healthcheck / uptime monitor.
     *
     * @unauthenticated
     */
    public function health(): JsonResponse
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
            Cache::put('health:ping', 1, 10);
            $checks['cache'] = ['ok' => Cache::get('health:ping') === 1];
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

        return response()->json(['ok' => $ok, 'time' => now()->toIso8601String(), 'checks' => $checks], $ok ? 200 : 503);
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
