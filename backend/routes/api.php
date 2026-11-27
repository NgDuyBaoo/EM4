<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\SystemController;
use Illuminate\Support\Facades\Route;

// Tài liệu API (Scramble): /docs/api   ·   OpenAPI JSON: /docs/api.json

Route::get('health', [SystemController::class, 'health'])->name('api.health');
Route::get('weather', [SystemController::class, 'weather'])->name('api.weather');

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

// Công khai: xem danh mục, gửi lời nhắn
Route::apiResource('branches', BranchController::class)->only(['index', 'show']);
Route::apiResource('rooms', RoomController::class)->only(['index', 'show']);
Route::post('messages', [MessageController::class, 'store'])->middleware('throttle:access-code');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('branches/{branch}/availability', [BranchController::class, 'availability'])->middleware('throttle:access-code');
    Route::apiResource('bookings', BookingController::class);

    // Chủ cửa hàng (kiểm tra bằng Policy)
    Route::apiResource('branches', BranchController::class)->except(['index', 'show']);
    Route::apiResource('rooms', RoomController::class)->except(['index', 'show']);

    // Nhân viên
    Route::middleware(['staff', 'abilities:staff'])->prefix('admin')->group(function () {
        Route::get('access-code', [AdminController::class, 'accessCode']);
        Route::post('access-code/regenerate', [AdminController::class, 'regenerateAccessCode']);
        Route::get('reports/summary', [AdminController::class, 'report']);
        Route::apiResource('messages', MessageController::class)->except('store');
    });
});
