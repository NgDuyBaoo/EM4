@extends('layouts.app', ['title' => '404 – Ngói Photobooth'])

@section('content')
<div class="card mx-auto max-w-md text-center">
    <div class="font-serif text-6xl text-ngoi-brand">404</div>
    <p class="mt-2">{{ $exception?->getMessage() && 404 === 403 ? $exception->getMessage() : 'Không tìm thấy trang.' }}</p>
    <a href="{{ route('home') }}" class="btn mt-5">Về trang chủ</a>
</div>
@endsection
