<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\LoginRequest;
use App\Http\Requests\Web\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/** Đăng ký / đăng nhập / đăng xuất bằng form Blade (có @csrf, kiểm tra dữ liệu bằng FormRequest). */
class AuthController extends Controller
{
    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());
        Auth::login($user);
        $request->session()->regenerate();
        Log::info('Khách đăng ký tài khoản', ['user_id' => $user->id]);

        return redirect()->route('booking')->with('status', 'Chào mừng bạn đến với Ngói! Nhập mã truy cập hôm nay để đặt lịch.');
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();
        $user = $request->user();

        return redirect()->intended($user->isStaff() ? route('admin.schedule') : route('booking'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
