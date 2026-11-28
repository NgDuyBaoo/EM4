@extends('layouts.app')

@section('content')
<section class="text-center">
    <h1 class="font-serif text-4xl sm:text-5xl">Simple moments,<br>lasting memories.</h1>
    <p class="mx-auto mt-3 max-w-xl text-ngoi-muted">Đặt phòng chụp photobooth tại Ngói chỉ trong 1 phút. Mỗi slot 10 phút, mở cửa 08:30–22:30, đặt lịch trong ngày bằng mã truy cập có tại quầy.</p>
    <div class="mt-6 flex justify-center gap-3">
        <a href="{{ route('booking') }}" class="btn">Đặt lịch ngay</a>
        @guest <a href="{{ route('register') }}" class="btn-ghost">Tạo tài khoản</a> @endguest
    </div>
</section>

<x-weather :weather="$weather" class="mx-auto mt-8 max-w-xl" />

<section class="mt-10">
    <h2 class="mb-3 font-serif text-2xl">Chi nhánh</h2>
    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($branches as $b)
            <article class="card">
                <h3 class="font-serif text-xl">{{ $b->name }}</h3>
                <p class="text-sm text-ngoi-muted">{{ $b->address }} · {{ $b->openAt() }}–{{ $b->closeAt() }}</p>
                <ul class="mt-3 space-y-1 text-sm">
                    @foreach ($b->activeRooms as $r)
                        <li>· {{ $r->label() }} — {{ number_format($r->price_per_slot, 0, ',', '.') }}đ / 10 phút</li>
                    @endforeach
                </ul>
                <a class="mt-3 inline-block text-sm font-semibold text-ngoi-brand underline" href="{{ $b->mapUrl() }}" target="_blank" rel="noopener">Xem Google Maps ↗</a>
            </article>
        @endforeach
    </div>
</section>

<section class="mt-10">
    <h2 class="mb-1 font-serif text-2xl">Phông nền</h2>
    <p class="mb-3 text-sm text-ngoi-muted">Một vài phông nền đang có tại tiệm. Phông thay theo mùa, hỏi nhân viên khi đến nhé.</p>
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach (config('ngoi.backdrops') as $bd)
            <figure class="overflow-hidden rounded-xl bg-ngoi-muted/10">
                <x-responsive-image :src="'images/backdrops/'.$bd['slug']" :alt="'Phông nền '.$bd['name']"
                    :width="$bd['width']" :height="$bd['height']"
                    sizes="(min-width: 1024px) 240px, 50vw"
                    class="aspect-[3/4] h-auto w-full object-cover" />
                <figcaption class="px-2 py-1.5 text-center text-sm">{{ $bd['name'] }}</figcaption>
            </figure>
        @endforeach
    </div>
</section>
@endsection
