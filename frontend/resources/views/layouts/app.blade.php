<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#7A4A2B">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Ngói Photobooth – Đặt lịch chụp ảnh tại Huế' }}</title>
    <meta name="description" content="Đặt lịch chụp photobooth tại Ngói, 2 chi nhánh ở Huế. Slot 10 phút, 08:30–22:30.">
    <link rel="icon" href="/favicon.svg">
    {{-- preconnect + display=swap: font không chặn hiển thị trang --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Be+Vietnam+Pro:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['../frontend/resources/css/app.css', '../frontend/resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-ngoi-bg font-sans text-ngoi-ink antialiased">
    <header class="roof relative mb-4 flex flex-wrap items-center justify-between gap-3 px-5 py-3.5 text-ngoi-brand-ink">
        <a href="{{ route('home') }}" wire:navigate class="font-serif text-3xl font-bold leading-none">
            Ngói<span class="block font-sans text-[9px] font-medium tracking-[.3em]">PHOTOBOOTH</span>
        </a>
        <nav class="flex flex-wrap items-center gap-1 rounded-full bg-black/20 p-1 text-sm" aria-label="Điều hướng">
            @php($link = fn ($r) => request()->routeIs($r) ? 'bg-ngoi-brand-ink text-ngoi-brand' : 'hover:bg-white/10')
            <a wire:navigate href="{{ route('booking') }}" class="rounded-full px-3.5 py-1.5 {{ $link('booking') }}">Đặt lịch</a>
            @auth
                <a wire:navigate href="{{ route('my-bookings') }}" class="rounded-full px-3.5 py-1.5 {{ $link('my-bookings') }}">Lịch của tôi</a>
            @endauth
            <a wire:navigate href="{{ route('contact') }}" class="rounded-full px-3.5 py-1.5 {{ $link('contact') }}">Liên hệ</a>
            @auth
                @if (auth()->user()->isStaff())
                    <a href="{{ route('admin.schedule') }}" class="rounded-full px-3.5 py-1.5 hover:bg-white/10">Quản lý</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-full px-3.5 py-1.5 hover:bg-white/10" title="{{ auth()->user()->email }}">Đăng xuất</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="rounded-full px-3.5 py-1.5 {{ $link('login') }}">Đăng nhập</a>
            @endauth
        </nav>
    </header>

    <main class="mx-auto max-w-4xl px-4 pb-12 pt-6">
        @if (session('status'))
            <div class="card mb-4 border-emerald-300 bg-emerald-50 text-emerald-900" role="status">{{ session('status') }}</div>
        @endif
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <footer class="px-4 py-6 text-center text-xs text-ngoi-muted">
        Ngói Photobooth · Simple moments, lasting memories · Mở cửa 08:30–22:30 hằng ngày
    </footer>
    @livewireScripts
</body>
</html>
