@extends('layouts.app', ['title' => '503 – Ngói Photobooth'])

@section('content')
<div class="card mx-auto max-w-md text-center">
    <div class="font-serif text-6xl text-ngoi-brand">503</div>
    <p class="mt-2">{{ $exception?->getMessage() && 503 === 403 ? $exception->getMessage() : 'Hệ thống đang bảo trì, vui lòng quay lại sau.' }}</p>
    <a href="{{ route('home') }}" class="btn mt-5">Về trang chủ</a>
</div>
@endsection
