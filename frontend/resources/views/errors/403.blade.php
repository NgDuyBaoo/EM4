@extends('layouts.app', ['title' => '403 – Ngói Photobooth'])

@section('content')
<div class="card mx-auto max-w-md text-center">
    <div class="font-serif text-6xl text-ngoi-brand">403</div>
    <p class="mt-2">{{ $exception?->getMessage() && 403 === 403 ? $exception->getMessage() : 'Bạn không có quyền vào trang này.' }}</p>
    <a href="{{ route('home') }}" class="btn mt-5">Về trang chủ</a>
</div>
@endsection
