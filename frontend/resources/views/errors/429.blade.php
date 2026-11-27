@extends('layouts.app', ['title' => '429 – Ngói Photobooth'])

@section('content')
<div class="card mx-auto max-w-md text-center">
    <div class="font-serif text-6xl text-ngoi-brand">429</div>
    <p class="mt-2">{{ $exception?->getMessage() && 429 === 403 ? $exception->getMessage() : 'Bạn thao tác quá nhanh, vui lòng thử lại sau ít phút.' }}</p>
    <a href="{{ route('home') }}" class="btn mt-5">Về trang chủ</a>
</div>
@endsection
