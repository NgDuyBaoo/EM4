<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Dữ liệu cá nhân (API cần token): cấm trình duyệt và proxy lưu lại. */
class NoStoreResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
