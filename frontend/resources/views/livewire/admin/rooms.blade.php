<div>
    <h1 class="font-serif text-3xl">Phòng & giá</h1>
    <p class="mb-4 text-ngoi-muted">Tắt phòng để ngừng nhận đặt online. Xóa phòng là xóa mềm, lịch sử booking vẫn giữ.</p>
    <div class="mb-4 flex items-end gap-3">
        <div><label class="label" for="br">Chi nhánh</label>
            <select id="br" wire:model.live="branchId" class="field">@foreach ($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
        <button class="btn mb-0.5" wire:click="create">+ Thêm phòng</button>
    </div>
    @if ($flash) <div class="card mb-3 py-3" role="status">{{ $flash }}</div> @endif

    @if ($editingId !== null)
        <form wire:submit="save" class="card mb-4 grid gap-3 sm:grid-cols-2">
            <h2 class="font-serif text-xl sm:col-span-2">{{ $editingId ? 'Sửa phòng' : 'Thêm phòng' }}</h2>
            <div><label class="label" for="fn">Tên phòng</label><input id="fn" wire:model="form.name" class="field">@error('form.name')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="fs">Phong cách</label><input id="fs" wire:model="form.style" class="field"></div>
            <div class="sm:col-span-2"><label class="label" for="fd">Mô tả</label><input id="fd" wire:model="form.description" class="field"></div>
            <div><label class="label" for="fp">Giá / slot 10 phút (đ)</label><input id="fp" type="number" step="1000" min="0" wire:model="form.price_per_slot" class="field">@error('form.price_per_slot')<p class="error">{{ $message }}</p>@enderror</div>
            <label class="flex items-center gap-2 self-end pb-3"><input type="checkbox" wire:model="form.is_active" class="accent-ngoi-brand"> Đang hoạt động</label>
            <div class="flex gap-2 sm:col-span-2"><button class="btn">Lưu</button><button type="button" class="btn-ghost" wire:click="$set('editingId', null)">Hủy</button></div>
        </form>
    @endif

    <div class="grid gap-4 md:grid-cols-3">
        @foreach ($rooms as $room)
            <div class="card" wire:key="room-{{ $room->id }}">
                <div class="flex items-start justify-between">
                    <h3 class="font-serif text-xl">{{ $room->label() }}</h3>
                    <span class="pill {{ $room->is_active ? 'bg-emerald-200 text-emerald-950' : 'bg-stone-300' }}">{{ $room->is_active ? 'Hoạt động' : 'Tạm ngừng' }}</span>
                </div>
                <p class="text-sm text-ngoi-muted">{{ $room->description }}</p>
                <p class="mt-2">{{ number_format($room->price_per_slot, 0, ',', '.') }}đ / slot · {{ $room->bookings_count }} lượt / 30 ngày</p>
                <div class="mt-3 flex gap-2">
                    <button class="btn btn-sm" wire:click="edit({{ $room->id }})">Sửa</button>
                    <button class="btn-ghost btn-sm" wire:click="delete({{ $room->id }})" wire:confirm="Xóa {{ $room->name }}?">Xóa</button>
                </div>
            </div>
        @endforeach
    </div>
</div>
