@extends('layouts.app', ['title' => '419 – Ngói Photobooth'])

@section('content')
<div class="card mx-auto max-w-md text-center">
    <div class="font-serif text-6xl text-ngoi-brand">419</div>
    <p class="mt-2">{{ $exception?->getMessage() && 419 === 403 ? $exception->getMessage() : 'Phiên làm việc đã hết hạn, vui lòng tải lại trang.' }}</p>
    <a href="{{ route('home') }}" class="btn mt-5">Về trang chủ</a>
</div>
@endsection
