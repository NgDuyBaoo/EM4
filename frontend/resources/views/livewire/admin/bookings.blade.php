<div>
    <h1 class="font-serif text-3xl">Quản lý booking</h1>
    <p class="mb-4 text-ngoi-muted">Xem chi tiết, check-in, hủy hoặc đánh dấu hoàn thành.</p>
    <div class="mb-3 flex flex-wrap items-end gap-3">
        <div><label class="label" for="br">Chi nhánh</label>
            <select id="br" wire:model.live="branchId" class="field"><option value="">Tất cả</option>@foreach ($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
        <div><label class="label" for="dt">Ngày</label><input id="dt" type="date" wire:model.live="date" class="field"></div>
        <div class="min-w-60 flex-1"><label class="label" for="q">Tìm kiếm</label><input id="q" wire:model.live.debounce.300ms="search" placeholder="Tên, SĐT hoặc mã booking" class="field"></div>
    </div>
    <div class="mb-3 flex flex-wrap gap-2">
        <button class="chip {{ $status === 'all' ? 'chip-on' : '' }}" wire:click="$set('status', 'all')">Tất cả</button>
        @foreach ($statuses as $s)
            <button class="chip {{ $status === $s->value ? 'chip-on' : '' }}" wire:click="$set('status', '{{ $s->value }}')">{{ $s->label() }}</button>
        @endforeach
    </div>
    @if ($flash) <div class="card mb-3 py-3" role="status">{{ $flash }}</div> @endif
    <div class="card overflow-x-auto p-2">
        <table class="w-full whitespace-nowrap text-sm">
            <thead class="text-left text-ngoi-muted"><tr>
                <th class="p-2">Ngày</th><th class="p-2">Giờ</th><th class="p-2">Phòng</th><th class="p-2">Khách</th><th class="p-2">SĐT</th><th class="p-2">Mã</th><th class="p-2">Giá</th><th class="p-2">Trạng thái</th><th class="p-2">Thao tác</th>
            </tr></thead>
            <tbody>
                @forelse ($bookings as $b)
                    <tr class="border-t border-ngoi-line" wire:key="bk-{{ $b->id }}">
                        <td class="p-2">{{ $b->booking_date->format('d/m') }}</td>
                        <td class="p-2 font-semibold">{{ $b->time() }}</td>
                        <td class="p-2">{{ $b->room->name }} <span class="text-ngoi-muted">· {{ $b->branch->name }}</span></td>
                        <td class="p-2">{{ $b->customer_name }} @if ($b->source === \App\Enums\BookingSource::Walkin)<span class="text-ngoi-muted">(quầy)</span>@endif</td>
                        <td class="p-2">{{ $b->customer_phone }}</td>
                        <td class="p-2">{{ $b->booking_code }}</td>
                        <td class="p-2">{{ number_format($b->price, 0, ',', '.') }}đ</td>
                        <td class="p-2"><x-status-pill :status="$b->status" /></td>
                        <td class="flex gap-1 p-2">@include('livewire.admin.partials.actions', ['b' => $b])</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="p-4 text-ngoi-muted">Không có booking phù hợp.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $bookings->links() }}</div>
</div>
