@extends('layouts.app', ['title' => 'Đăng ký – Ngói Photobooth'])

@section('content')
<form method="POST" action="{{ route('register') }}" class="card mx-auto max-w-md" novalidate>
    @csrf
    <h1 class="font-serif text-3xl">Tạo tài khoản</h1>
    <p class="mb-5 text-ngoi-muted">Lưu lịch đã đặt, nhận email xác nhận và hủy lịch khi bận.</p>

    @foreach ([['name', 'Họ và tên', 'text', 'name'], ['email', 'Email', 'email', 'email'], ['phone', 'Số điện thoại', 'tel', 'tel']] as [$f, $l, $t, $ac])
        <label for="{{ $f }}" class="label {{ $loop->first ? '' : 'mt-3' }}">{{ $l }}</label>
        <input id="{{ $f }}" name="{{ $f }}" type="{{ $t }}" value="{{ old($f) }}" autocomplete="{{ $ac }}" required class="field">
        @error($f) <p class="error">{{ $message }}</p> @enderror
    @endforeach

    <label for="password" class="label mt-3">Mật khẩu (ít nhất 8 ký tự, có chữ và số)</label>
    <input id="password" name="password" type="password" autocomplete="new-password" required class="field">
    @error('password') <p class="error">{{ $message }}</p> @enderror

    <label for="password_confirmation" class="label mt-3">Nhập lại mật khẩu</label>
    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="field">

    <button class="btn mt-5 w-full">Đăng ký</button>
    <p class="mt-4 text-center text-sm">Đã có tài khoản? <a href="{{ route('login') }}" class="font-semibold text-ngoi-brand underline">Đăng nhập</a></p>
</form>
@endsection
