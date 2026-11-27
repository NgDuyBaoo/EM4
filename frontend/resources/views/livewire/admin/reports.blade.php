@php($vnd = fn ($n) => number_format($n, 0, ',', '.').'đ')
<div>
    <h1 class="font-serif text-3xl">Thống kê & báo cáo</h1>
    <p class="mb-4 text-ngoi-muted">Lượt đặt không tính booking đã hủy. Doanh thu chỉ tính booking hoàn thành. Số liệu cập nhật mỗi 5 phút.</p>
    <div class="mb-4 flex flex-wrap items-end gap-3">
        <div><label class="label" for="br">Chi nhánh</label>
            <select id="br" wire:model.live="branchId" class="field"><option value="">Tất cả chi nhánh</option>@foreach ($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
        <div class="flex gap-2 pb-1">
            @foreach ([7 => '7 ngày', 30 => '30 ngày', 90 => '90 ngày'] as $v => $l)
                <button class="chip {{ $days === $v ? 'chip-on' : '' }}" wire:click="$set('days', {{ $v }})">{{ $l }}</button>
            @endforeach
        </div>
        <button class="btn-ghost btn-sm mb-1" wire:click="export">Xuất CSV</button>
    </div>

    <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="card py-4"><b class="block font-serif text-3xl">{{ $r['totals']['bookings'] }}</b><span class="text-sm text-ngoi-muted">Tổng lượt đặt</span></div>
        <div class="card py-4"><b class="block font-serif text-3xl">{{ $r['totals']['completed'] }}</b><span class="text-sm text-ngoi-muted">Hoàn thành</span></div>
        <div class="card py-4"><b class="block font-serif text-3xl">{{ $r['totals']['cancelled'] }}</b><span class="text-sm text-ngoi-muted">Đã hủy</span></div>
        <div class="card py-4"><b class="block font-serif text-3xl">{{ $vnd($r['totals']['revenue']) }}</b><span class="text-sm text-ngoi-muted">Doanh thu</span></div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach (['bookings' => ['Lượt đặt theo ngày', fn ($v) => $v.' lượt'], 'revenue' => ['Doanh thu theo ngày', $vnd]] as $field => [$label, $fmt])
            @php($max = max(1, max(array_column($r['days'], $field))))
            @php($every = (int) ceil(count($r['days']) / 15))
            <figure class="card" aria-label="{{ $label }}">
                <figcaption class="mb-2 font-serif text-xl">{{ $label }}</figcaption>
                <div class="flex h-44 items-end gap-0.5 border-b border-ngoi-line pt-4">
                    @foreach ($r['days'] as $d)
                        <div class="group relative flex h-full flex-1 flex-col items-center justify-end">
                            <span class="pointer-events-none absolute bottom-full z-10 hidden whitespace-nowrap rounded bg-ngoi-ink px-2 py-1 text-xs text-ngoi-bg group-hover:block">{{ \Illuminate\Support\Carbon::parse($d['date'])->format('d/m') }}: {{ $fmt($d[$field]) }}</span>
                            @if ($d[$field] > 0)<div class="w-full max-w-7 rounded-t bg-ngoi-brand group-hover:brightness-125" style="height: {{ $d[$field] / $max * 100 }}%"></div>@endif
                        </div>
                    @endforeach
                </div>
                <div class="flex gap-0.5 text-[10px] text-ngoi-muted">
                    @foreach ($r['days'] as $i => $d)<span class="flex-1 overflow-hidden text-center">{{ $i % $every === 0 ? \Illuminate\Support\Carbon::parse($d['date'])->format('d/m') : '' }}</span>@endforeach
                </div>
            </figure>
        @endforeach
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <div class="card overflow-x-auto">
            <h2 class="mb-2 font-serif text-xl">Hiệu suất từng phòng</h2>
            <table class="w-full text-sm"><thead class="text-left text-ngoi-muted"><tr><th class="p-2">Phòng</th><th class="p-2">Lượt</th><th class="p-2">Lấp đầy</th><th class="p-2">Doanh thu</th></tr></thead>
                <tbody>@foreach ($r['rooms'] as $room)
                    <tr class="border-t border-ngoi-line"><td class="p-2">{{ $room['room'] }}<br><span class="text-xs text-ngoi-muted">{{ $room['branch'] }}</span></td><td class="p-2">{{ $room['bookings'] }}</td>
                        <td class="p-2"><div class="h-2 w-24 overflow-hidden rounded bg-ngoi-line"><div class="h-full rounded bg-ngoi-brand" style="width: {{ min(100, $room['occupancy'] * 100) }}%"></div></div><span class="text-xs">{{ number_format($room['occupancy'] * 100, 1, ',', '.') }}%</span></td>
                        <td class="p-2">{{ $vnd($room['revenue']) }}</td></tr>
                @endforeach</tbody></table>
        </div>
        <div class="card overflow-x-auto">
            <h2 class="mb-2 font-serif text-xl">Báo cáo theo chi nhánh</h2>
            <table class="w-full text-sm"><thead class="text-left text-ngoi-muted"><tr><th class="p-2">Chi nhánh</th><th class="p-2">Lượt đặt</th><th class="p-2">Đã hủy</th><th class="p-2">Doanh thu</th></tr></thead>
                <tbody>@foreach ($r['branches'] as $b)<tr class="border-t border-ngoi-line"><td class="p-2">{{ $b['branch'] }}</td><td class="p-2">{{ $b['bookings'] }}</td><td class="p-2">{{ $b['cancelled'] }}</td><td class="p-2">{{ $vnd($b['revenue']) }}</td></tr>@endforeach</tbody></table>
        </div>
    </div>
    <details class="card mt-4"><summary class="cursor-pointer">Bảng số liệu theo ngày</summary>
        <table class="mt-2 w-full text-sm"><thead class="text-left text-ngoi-muted"><tr><th class="p-2">Ngày</th><th class="p-2">Lượt</th><th class="p-2">Hoàn thành</th><th class="p-2">Hủy</th><th class="p-2">Doanh thu</th></tr></thead>
            <tbody>@foreach ($r['days'] as $d)<tr class="border-t border-ngoi-line"><td class="p-2">{{ $d['date'] }}</td><td class="p-2">{{ $d['bookings'] }}</td><td class="p-2">{{ $d['completed'] }}</td><td class="p-2">{{ $d['cancelled'] }}</td><td class="p-2">{{ $vnd($d['revenue']) }}</td></tr>@endforeach</tbody></table>
    </details>
</div>
