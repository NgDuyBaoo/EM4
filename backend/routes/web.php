<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\PageController;
use App\Livewire\Admin;
use App\Livewire\Customer;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/lien-he', [PageController::class, 'contact'])->name('contact');
Route::post('/lien-he', [PageController::class, 'sendMessage'])->middleware('throttle:access-code')->name('contact.send');

Route::middleware('guest')->group(function () {
    Route::get('/dang-ky', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/dang-ky', [AuthController::class, 'register'])->middleware('throttle:login');
    Route::get('/dang-nhap', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/dang-nhap', [AuthController::class, 'login'])->middleware('throttle:login');
});

Route::middleware('auth')->group(function () {
    Route::post('/dang-xuat', [AuthController::class, 'logout'])->name('logout');

    // Khách đặt lịch (Livewire)
    Route::get('/dat-lich', Customer\BookingWizard::class)->name('booking');
    Route::get('/lich-cua-toi', Customer\MyBookings::class)->name('my-bookings');

    // Quản lý (nhân viên / chủ cửa hàng)
    Route::middleware('staff')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', Admin\Schedule::class)->name('schedule');
        Route::get('/bookings', Admin\Bookings::class)->name('bookings');
        Route::get('/thong-ke', Admin\Reports::class)->name('reports');
        Route::get('/loi-nhan', Admin\Messages::class)->name('messages');
        Route::get('/cai-dat', Admin\Settings::class)->name('settings');
        Route::get('/phong', Admin\Rooms::class)->middleware('staff:owner')->name('rooms');
    });
});
