<?php

use App\Exceptions\BookingException;
use App\Http\Middleware\EnsureStaff;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['staff' => EnsureStaff::class]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('home'));
        $middleware->throttleApi('api');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Lỗi nghiệp vụ là bình thường: ghi vào log booking (BookingException::report), không báo lỗi hệ thống.
        $exceptions->dontReportDuplicates();

        // API luôn trả JSON, thông điệp tiếng Việt, không lộ stack trace.
        $exceptions->shouldRenderJsonWhen(fn (Request $r) => $r->is('api/*') || $r->expectsJson());

        $exceptions->render(function (AuthenticationException $e, Request $r) {
            if ($r->is('api/*')) {
                return response()->json(['message' => 'Bạn cần đăng nhập (thiếu hoặc sai token).', 'code' => 'UNAUTHENTICATED'], 401);
            }
        });
        $exceptions->render(function (AuthorizationException|HttpException $e, Request $r) {
            if ($r->is('api/*') && ($e instanceof AuthorizationException || $e->getStatusCode() === 403)) {
                return response()->json(['message' => 'Bạn không có quyền thực hiện thao tác này.', 'code' => 'FORBIDDEN'], 403);
            }
        });
        $exceptions->render(function (NotFoundHttpException $e, Request $r) {
            if ($r->is('api/*')) {
                return response()->json(['message' => 'Không tìm thấy dữ liệu.', 'code' => 'NOT_FOUND'], 404);
            }
        });
        $exceptions->render(function (ValidationException $e, Request $r) {
            if ($r->is('api/*')) {
                return response()->json(['message' => $e->getMessage(), 'code' => 'VALIDATION_ERROR', 'errors' => $e->errors()], 422);
            }
        });

        // Thêm ngữ cảnh vào mọi log lỗi để dễ tra
        $exceptions->context(fn () => [
            'url' => request()?->fullUrl(),
            'user_id' => auth()->id(),
        ]);
    })->create();
