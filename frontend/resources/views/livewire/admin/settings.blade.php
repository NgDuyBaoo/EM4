<div>
    <h1 class="font-serif text-3xl">Cài đặt</h1>
    <p class="mb-4 text-ngoi-muted">Đăng nhập với {{ auth()->user()->email }} ({{ auth()->user()->role->label() }}).</p>
    @if ($flash) <div class="card mb-3 py-3" role="status">{{ $flash }}</div> @endif
    <div class="grid gap-4 md:grid-cols-2">
        <div class="card">
            <h2 class="font-serif text-xl">Mã truy cập hôm nay</h2>
            <p class="text-sm text-ngoi-muted">Dùng chung cho 2 chi nhánh, tự đổi lúc 00:00 giờ Việt Nam.</p>
            <div class="my-3 font-serif text-5xl font-bold tracking-[.15em] text-ngoi-brand">{{ $code->code }}</div>
            @if ($confirmRegen)
                <button class="btn btn-sm btn-danger" wire:click="regenerate">Đổi mã ngay</button>
                <button class="btn-ghost btn-sm" wire:click="$set('confirmRegen', false)">Thôi</button>
            @else
                <button class="btn-ghost btn-sm" wire:click="$set('confirmRegen', true)">Tạo mã mới (khi mã bị lộ)</button>
            @endif
        </div>
        <form wire:submit="changePassword" class="card">
            <h2 class="mb-2 font-serif text-xl">Đổi mật khẩu</h2>
            <input type="password" wire:model="currentPassword" placeholder="Mật khẩu hiện tại" autocomplete="current-password" class="field">
            @error('currentPassword') <p class="error">{{ $message }}</p> @enderror
            <input type="password" wire:model="password" placeholder="Mật khẩu mới (ít nhất 8 ký tự)" autocomplete="new-password" class="field mt-3">
            @error('password') <p class="error">{{ $message }}</p> @enderror
            <input type="password" wire:model="password_confirmation" placeholder="Nhập lại mật khẩu mới" autocomplete="new-password" class="field mt-3">
            <button class="btn btn-sm mt-3">Lưu mật khẩu</button>
        </form>
    </div>
</div>
