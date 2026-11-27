@extends('layouts.app', ['title' => 'Liên hệ – Ngói Photobooth'])

@section('content')
<h1 class="font-serif text-3xl">Liên hệ Ngói</h1>
<p class="mb-5 text-ngoi-muted">Gửi lời nhắn hoặc xem địa chỉ chi nhánh.</p>
<div class="grid gap-4 md:grid-cols-[1.4fr_1fr]">
    <form method="POST" action="{{ route('contact.send') }}" class="card" novalidate>
        @csrf
        <h2 class="mb-3 font-serif text-xl">Gửi lời nhắn</h2>
        <label for="branch_id" class="label">Chi nhánh</label>
        <select id="branch_id" name="branch_id" class="field">
            <option value="">Góp ý chung</option>
            @foreach ($branches as $b)
                <option value="{{ $b->id }}" @selected(old('branch_id') == $b->id)>{{ $b->name }}</option>
            @endforeach
        </select>
        <label for="subject" class="label mt-3">Nội dung liên hệ</label>
        <select id="subject" name="subject" class="field">
            @foreach (['Nhắn tin riêng', 'Góp ý dịch vụ', 'Hỏi về lịch đã đặt', 'Khác'] as $s)
                <option @selected(old('subject') === $s)>{{ $s }}</option>
            @endforeach
        </select>
        <label for="name" class="label mt-3">Họ và tên (không bắt buộc)</label>
        <input id="name" name="name" value="{{ old('name', auth()->user()?->name) }}" maxlength="100" class="field">
        <label for="contact" class="label mt-3">Điện thoại / email *</label>
        <input id="contact" name="contact" value="{{ old('contact', auth()->user()?->phone) }}" maxlength="120" class="field">
        @error('contact') <p class="error">{{ $message }}</p> @enderror
        <label for="body" class="label mt-3">Lời nhắn *</label>
        <textarea id="body" name="body" rows="4" maxlength="2000" class="field">{{ old('body') }}</textarea>
        @error('body') <p class="error">{{ $message }}</p> @enderror
        <button class="btn mt-4">Gửi lời nhắn</button>
    </form>
    <aside class="card">
        <h2 class="mb-2 font-serif text-xl">Chi nhánh Ngói</h2>
        @foreach ($branches as $b)
            <div class="border-b border-ngoi-line py-3 last:border-0">
                <b>0{{ $loop->iteration }} · {{ $b->name }}</b><br>
                <span class="text-sm text-ngoi-muted">{{ $b->address }}</span><br>
                <span class="text-sm">SĐT: {{ $b->phone ?: 'Đang cập nhật' }}</span> ·
                <a class="text-sm text-ngoi-brand underline" href="{{ $b->mapUrl() }}" target="_blank" rel="noopener">Google Maps ↗</a>
            </div>
        @endforeach
    </aside>
</div>
@endsection
