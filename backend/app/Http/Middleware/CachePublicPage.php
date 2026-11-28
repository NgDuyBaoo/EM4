<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cache toàn trang (prerender) cho trang công khai khi khách chưa đăng nhập.
 *
 * - Chỉ cache GET, khách chưa đăng nhập, không có thông báo flash / lỗi form.
 * - Token CSRF được thay bằng chỗ trống khi lưu và điền token của phiên hiện tại khi trả về,
 *   nên form liên hệ vẫn hợp lệ với mọi khách.
 * - Danh mục chi nhánh/phòng thay đổi thì CatalogObserver tăng phiên bản cache, trang được dựng lại.
 */
class CachePublicPage
{
    public const VERSION_KEY = 'page-cache:version';

    private const CSRF_PLACEHOLDER = '__NGOI_CSRF_TOKEN__';

    public function handle(Request $request, Closure $next, int $seconds = 600): Response
    {
        if (! $this->cacheable($request)) {
            return $next($request);
        }

        $key = 'page-cache:'.Cache::get(self::VERSION_KEY, 1).':'.$request->path();
        $html = Cache::get($key);

        if ($html !== null) {
            return response(str_replace(self::CSRF_PLACEHOLDER, csrf_token(), $html))
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('X-Page-Cache', 'HIT');
        }

        $response = $next($request);

        if ($response->getStatusCode() === 200 && str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            Cache::put($key, str_replace(csrf_token(), self::CSRF_PLACEHOLDER, (string) $response->getContent()), $seconds);
            $response->headers->set('X-Page-Cache', 'MISS');
        }

        return $response;
    }

    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, (int) Cache::get(self::VERSION_KEY, 1) + 1);
    }

    private function cacheable(Request $request): bool
    {
        return $request->isMethod('GET')
            && $request->query->count() === 0
            && ! $request->user()
            && ! $request->session()->has('status')
            && ! $request->session()->has('errors')
            && ! $request->session()->hasOldInput();
    }
}
