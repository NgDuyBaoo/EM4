<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Quản lý – Ngói Photobooth' }}</title>
    <link rel="icon" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Be+Vietnam+Pro:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['../frontend/resources/css/app.css', '../frontend/resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-ngoi-bg font-sans text-ngoi-ink antialiased">
    <header class="roof relative mb-4 flex flex-wrap items-center justify-between gap-3 px-5 py-3.5 text-ngoi-brand-ink">
        <a href="{{ route('admin.schedule') }}" wire:navigate class="font-serif text-3xl font-bold leading-none">
            Ngói<span class="block font-sans text-[9px] font-medium tracking-[.3em]">QUẢN LÝ</span>
        </a>
        <nav class="flex flex-wrap items-center gap-1 rounded-full bg-black/20 p-1 text-sm" aria-label="Menu quản lý">
            @php($link = fn ($r) => request()->routeIs($r) ? 'bg-ngoi-brand-ink text-ngoi-brand' : 'hover:bg-white/10')
            <a wire:navigate href="{{ route('admin.schedule') }}" class="rounded-full px-3.5 py-1.5 {{ $link('admin.schedule') }}">Lịch phòng</a>
            <a wire:navigate href="{{ route('admin.bookings') }}" class="rounded-full px-3.5 py-1.5 {{ $link('admin.bookings') }}">Booking</a>
            <a wire:navigate href="{{ route('admin.reports') }}" class="rounded-full px-3.5 py-1.5 {{ $link('admin.reports') }}">Thống kê</a>
            @if (auth()->user()->isOwner())
                <a wire:navigate href="{{ route('admin.rooms') }}" class="rounded-full px-3.5 py-1.5 {{ $link('admin.rooms') }}">Phòng</a>
            @endif
            <a wire:navigate href="{{ route('admin.messages') }}" class="rounded-full px-3.5 py-1.5 {{ $link('admin.messages') }}">Lời nhắn</a>
            <a wire:navigate href="{{ route('admin.settings') }}" class="rounded-full px-3.5 py-1.5 {{ $link('admin.settings') }}">Cài đặt</a>
            @if (auth()->user()->isOwner())
                <a href="/telescope" class="rounded-full px-3.5 py-1.5 hover:bg-white/10" title="Telescope: giám sát request, query, job, lỗi">Telescope</a>
                <a href="/pulse" class="rounded-full px-3.5 py-1.5 hover:bg-white/10" title="Pulse: giám sát máy chủ">Pulse</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="rounded-full px-3.5 py-1.5 hover:bg-white/10">Đăng xuất</button>
            </form>
        </nav>
    </header>
    <main class="mx-auto max-w-7xl px-4 pb-12 pt-6">
        {{ $slot }}
    </main>
    @livewireScripts
</body>
</html>
