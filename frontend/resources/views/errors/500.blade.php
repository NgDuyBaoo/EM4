@extends('layouts.app', ['title' => '500 – Ngói Photobooth'])

@section('content')
<div class="card mx-auto max-w-md text-center">
    <div class="font-serif text-6xl text-ngoi-brand">500</div>
    <p class="mt-2">{{ $exception?->getMessage() && 500 === 403 ? $exception->getMessage() : 'Có lỗi hệ thống, Ngói đã ghi nhận và sẽ khắc phục sớm.' }}</p>
    <a href="{{ route('home') }}" class="btn mt-5">Về trang chủ</a>
</div>
@endsection
