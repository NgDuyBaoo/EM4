@extends('layouts.app', ['title' => 'Đăng nhập – Ngói Photobooth'])

@section('content')
<form method="POST" action="{{ route('login') }}" class="card mx-auto max-w-md" novalidate>
    @csrf
    <h1 class="font-serif text-3xl">Đăng nhập</h1>
    <p class="mb-5 text-ngoi-muted">Đăng nhập để đặt lịch và xem lịch của bạn.</p>

    <label for="email" class="label">Email</label>
    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus class="field">
    @error('email') <p class="error">{{ $message }}</p> @enderror

    <label for="password" class="label mt-3">Mật khẩu</label>
    <input id="password" name="password" type="password" autocomplete="current-password" required class="field">
    @error('password') <p class="error">{{ $message }}</p> @enderror

    <label class="mt-3 flex items-center gap-2 text-sm"><input type="checkbox" name="remember" class="accent-ngoi-brand"> Ghi nhớ đăng nhập</label>
    <button class="btn mt-5 w-full">Đăng nhập</button>
    <p class="mt-4 text-center text-sm">Chưa có tài khoản? <a href="{{ route('register') }}" class="font-semibold text-ngoi-brand underline">Đăng ký</a></p>
</form>
@endsection
