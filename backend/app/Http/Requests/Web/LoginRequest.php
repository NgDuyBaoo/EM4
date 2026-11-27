<?php

namespace App\Http\Requests\Web;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['email' => ['required', 'email'], 'password' => ['required', 'string']];
    }

    public function attributes(): array
    {
        return ['email' => 'email', 'password' => 'mật khẩu'];
    }

    /** Đăng nhập, khóa 1 phút sau 5 lần sai. */
    public function authenticate(): void
    {
        $key = Str::lower($this->input('email')).'|'.$this->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            event(new Lockout($this));
            throw ValidationException::withMessages(['email' => 'Bạn đăng nhập sai quá nhiều lần. Thử lại sau '.RateLimiter::availableIn($key).' giây.']);
        }
        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'Email hoặc mật khẩu chưa đúng.']);
        }
        RateLimiter::clear($key);
    }
}
