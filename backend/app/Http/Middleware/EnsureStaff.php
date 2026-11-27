<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Chỉ nhân viên / chủ cửa hàng. Dùng: ->middleware('staff') hoặc 'staff:owner'. */
class EnsureStaff
{
    public function handle(Request $request, Closure $next, ?string $level = null): Response
    {
        $user = $request->user();
        $ok = $user && ($level === 'owner' ? $user->isOwner() : $user->isStaff());
        abort_unless($ok, 403, 'Bạn không có quyền truy cập khu vực quản lý.');

        return $next($request);
    }
}
