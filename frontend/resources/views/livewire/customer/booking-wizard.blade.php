@php($vnd = fn ($n) => number_format($n, 0, ',', '.').'đ')
<div>
    {{-- Thanh tiến trình 6 bước --}}
    <div class="mb-5 flex gap-2" role="progressbar" aria-valuemin="1" aria-valuemax="6" aria-valuenow="{{ $step }}" aria-label="Bước {{ $step }}/6">
        @for ($i = 1; $i <= 6; $i++)
            <i class="h-1.5 flex-1 rounded-full {{ $i <= $step ? 'bg-ngoi-brand' : 'bg-ngoi-line' }}"></i>
        @endfor
    </div>

    @if ($notice)
        <div class="card mb-4 border-rose-300 bg-rose-50 text-rose-900" role="alert">{{ $notice }}</div>
    @endif

    {{-- Bước 1: mã truy cập --}}
    @if ($step === 1)
        <form wire:submit="verifyCode" class="card mx-auto max-w-md text-center">
            <h1 class="font-serif text-3xl">Nhập mã truy cập</h1>
            <p class="mb-4 text-ngoi-muted">Mã 6 số thay đổi mỗi ngày, có tại quầy Ngói.</p>
            <input wire:model="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" autofocus
                   aria-label="Mã truy cập 6 số"
                   class="field text-center font-serif text-3xl tracking-[.5em]" placeholder="······">
            @error('code') <p class="error" role="alert">{{ $message }}</p> @enderror
            <button class="btn mt-4 w-full" wire:loading.attr="disabled">Vào trang đặt lịch</button>
        </form>
    @endif

    {{-- Bước 2: chi nhánh --}}
    @if ($step === 2)
        <h1 class="font-serif text-3xl">Chọn chi nhánh</h1>
        <p class="mb-4 text-ngoi-muted">Ngói có {{ $this->branches->count() }} chi nhánh tại Huế.</p>
        @foreach ($this->branches as $b)
            <button type="button" wire:click="chooseBranch({{ $b->id }})" aria-pressed="{{ $branchId === $b->id ? 'true' : 'false' }}"
                    class="mb-3 flex w-full items-center gap-4 rounded-2xl border-[1.5px] p-4 text-left transition hover:-translate-y-px {{ $branchId === $b->id ? 'border-ngoi-brand bg-ngoi-sel ring-4 ring-ngoi-sel' : 'border-ngoi-line bg-ngoi-surface' }}">
                <span class="h-20 w-24 flex-none -rotate-3 rounded-md border-4 border-ngoi-surface bg-gradient-to-br from-[#E2C49C] via-[#B07A4D] to-ngoi-brand shadow"></span>
                <span>
                    <b class="block font-serif text-xl">{{ $b->name }}</b>
                    <span class="text-sm text-ngoi-muted">{{ $b->address }} · Mở cửa {{ $b->openAt() }}–{{ $b->closeAt() }} · {{ $b->active_rooms_count }} phòng</span>
                </span>
            </button>
        @endforeach
        <div class="mt-5 flex gap-3"><button class="btn" wire:click="go(3)" @disabled(! $branchId)>Tiếp tục</button></div>
    @endif

    {{-- Bước 3: phòng --}}
    @if ($step === 3)
        <h1 class="font-serif text-3xl">Chọn phòng chụp</h1>
        <p class="mb-4 text-ngoi-muted">Mỗi phòng một phong cách. Bạn có thể chọn nhiều phòng.</p>
        @forelse ($this->branch->activeRooms as $r)
            @php($on = in_array($r->id, $roomIds, true))
            <button type="button" wire:click="toggleRoom({{ $r->id }})" aria-pressed="{{ $on ? 'true' : 'false' }}"
                    class="mb-3 flex w-full items-center gap-4 rounded-2xl border-[1.5px] p-4 text-left transition hover:-translate-y-px {{ $on ? 'border-ngoi-brand bg-ngoi-sel ring-4 ring-ngoi-sel' : 'border-ngoi-line bg-ngoi-surface' }}">
                <span class="h-20 w-24 flex-none rotate-2 rounded-md border-4 border-ngoi-surface bg-gradient-to-br from-[#E2C49C] via-[#B07A4D] to-ngoi-brand shadow"></span>
                <span>
                    <b class="block font-serif text-xl">{{ $r->name }} <span class="font-sans text-base font-normal text-ngoi-muted">· {{ $r->style }}</span></b>
                    <span class="text-sm text-ngoi-muted">{{ $r->description }}<br>{{ $vnd($r->price_per_slot) }} / slot 10 phút</span>
                </span>
            </button>
        @empty
            <div class="card">Chi nhánh tạm chưa nhận đặt phòng. Vui lòng chọn chi nhánh khác.</div>
        @endforelse
        <div class="mt-5 flex gap-3">
            <button class="btn-ghost" wire:click="go(2)">Quay lại</button>
            <button class="btn" wire:click="go(4)" @disabled(! $roomIds)>Tiếp tục</button>
        </div>
    @endif

    {{-- Bước 4: khung giờ. Tự làm mới mỗi 30 giây để thấy slot vừa bị đặt --}}
    @if ($step === 4)
        <div wire:poll.30s>
            <h1 class="font-serif text-3xl">Chọn khung giờ</h1>
            <p class="mb-4 text-ngoi-muted">{{ \Illuminate\Support\Carbon::parse($today)->locale('vi')->isoFormat('dddd, DD/MM/YYYY') }} · mỗi slot 10 phút. Giờ đã qua hoặc đã có người đặt bị gạch.</p>
            <x-weather :weather="$weather" class="mb-4" />

            @php($rooms = collect($this->availability['rooms'])->whereIn('id', $roomIds)->values())
            @php($room = $rooms->firstWhere('id', $tab) ?? $rooms->first())
            @if (! $room)
                <div class="card">Phòng đã chọn hiện tạm ngừng. Vui lòng chọn phòng khác.</div>
            @else
                @if ($rooms->count() > 1)
                    <div class="mb-3 flex flex-wrap gap-2" role="tablist">
                        @foreach ($rooms as $r)
                            <button role="tab" aria-selected="{{ $r['id'] === $room['id'] ? 'true' : 'false' }}" wire:click="$set('tab', {{ $r['id'] }})"
                                    class="min-w-28 flex-1 rounded-xl border-[1.5px] px-3 py-2.5 font-semibold {{ $r['id'] === $room['id'] ? 'border-ngoi-brand bg-ngoi-brand text-ngoi-brand-ink' : 'border-ngoi-line bg-ngoi-surface' }}">
                                {{ $r['name'] }} · {{ $r['style'] }}
                            </button>
                        @endforeach
                    </div>
                @endif

                <div class="mb-2 font-semibold">Chọn khoảng giờ</div>
                <div class="mb-3 grid grid-cols-[repeat(auto-fill,minmax(110px,1fr))] gap-2">
                    @foreach ($this->ranges as $i => [$a, $b])
                        <button wire:click="$set('range', {{ $i }})" class="chip {{ $range === $i ? 'chip-on' : '' }}" aria-pressed="{{ $range === $i ? 'true' : 'false' }}">
                            {{ \App\Support\SlotClock::time($a) }}–{{ \App\Support\SlotClock::time($b) }}
                        </button>
                    @endforeach
                </div>

                @php([$from, $to] = $this->ranges[$range] ?? $this->ranges[0])
                @php($inRange = collect($room['slots'])->filter(fn ($s) => \App\Support\SlotClock::minutes($s['time']) >= $from && \App\Support\SlotClock::minutes($s['time']) < $to))
                @php($freeAll = collect($room['slots'])->where('state', 'free')->count())
                @if (! $freeAll)
                    <div class="card mb-3 text-sm"><b>{{ $room['name'] }} đã kín lịch hoặc hết giờ nhận khách hôm nay.</b> Hãy chọn phòng khác.</div>
                @elseif (! $inRange->where('state', 'free')->count())
                    <p class="mb-2 text-sm text-ngoi-muted">Khoảng giờ này đã kín. Còn {{ $freeAll }} khung trống ở các khoảng khác.</p>
                @endif

                <div class="grid max-h-80 grid-cols-4 gap-2 overflow-y-auto p-1 sm:grid-cols-[repeat(auto-fill,minmax(68px,1fr))]">
                    @foreach ($inRange as $s)
                        @php($k = $room['id'].'|'.$s['time'])
                        @php($why = ['booked' => 'Đã đặt', 'past' => 'Đã qua giờ'][$s['state']] ?? null)
                        <button wire:click="toggleSlot({{ $room['id'] }}, '{{ $s['time'] }}')" wire:key="slot-{{ $k }}"
                                class="slot {{ in_array($k, $selected, true) ? 'slot-on' : '' }}" @disabled($why)
                                title="{{ $why }}" aria-label="{{ $s['time'] }}{{ $why ? ' – '.$why : '' }}" aria-pressed="{{ in_array($k, $selected, true) ? 'true' : 'false' }}">
                            {{ $s['time'] }}
                        </button>
                    @endforeach
                </div>
            @endif

            @if ($selected)
                <div class="card mt-4">
                    <b>Đã chọn {{ count($selected) }} slot · Tổng {{ $vnd(array_sum(array_column($this->lines, 'price'))) }}</b>
                    <ul class="mt-2 text-sm">
                        @foreach ($this->lines as $l) <li>{{ $l['time'] }} · {{ $l['room'] }} · {{ $vnd($l['price']) }}</li> @endforeach
                    </ul>
                </div>
            @endif
            <div class="mt-5 flex gap-3">
                <button class="btn-ghost" wire:click="go(3)">Quay lại</button>
                <button class="btn" wire:click="go(5)" @disabled(! $selected)>Tiếp tục</button>
            </div>
        </div>
    @endif

    {{-- Bước 5: xác nhận --}}
    @if ($step === 5)
        <h1 class="font-serif text-3xl">Xác nhận thông tin</h1>
        <p class="mb-4 text-ngoi-muted">Kiểm tra lại trước khi đặt lịch.</p>
        <div class="card">
            <dl class="grid grid-cols-[110px_1fr] gap-2">
                <dt class="text-ngoi-muted">Chi nhánh</dt><dd class="font-medium">{{ $this->branch->name }} ({{ $this->branch->address }})</dd>
                @foreach ($this->lines as $l)
                    <dt class="text-ngoi-muted">{{ $l['time'] }}</dt><dd class="font-medium">{{ $l['room'] }} · {{ $vnd($l['price']) }}</dd>
                @endforeach
                <dt class="text-ngoi-muted">Tổng</dt><dd class="font-semibold">{{ $vnd(array_sum(array_column($this->lines, 'price'))) }}</dd>
            </dl>
        </div>
        <form wire:submit="confirm" class="mt-4">
            <label for="name" class="label">Tên của bạn</label>
            <input id="name" wire:model="name" maxlength="80" autocomplete="name" class="field">
            @error('name') <p class="error">{{ $message }}</p> @enderror
            <label for="phone" class="label mt-3">Số điện thoại</label>
            <input id="phone" wire:model="phone" inputmode="tel" maxlength="20" autocomplete="tel" class="field">
            @error('phone') <p class="error">{{ $message }}</p> @enderror
            <p class="mt-3 text-sm text-ngoi-muted">Vui lòng đến đúng giờ. Quá {{ config('ngoi.no_show_grace_minutes') }} phút chưa check-in, lịch sẽ tự hủy để nhường khách sau.</p>
            <div class="mt-5 flex gap-3">
                <button type="button" class="btn-ghost" wire:click="go(4)">Quay lại</button>
                <button class="btn" wire:loading.attr="disabled"><span wire:loading.remove wire:target="confirm">Xác nhận đặt lịch</span><span wire:loading wire:target="confirm">Đang giữ chỗ…</span></button>
            </div>
        </form>
    @endif

    {{-- Bước 6: thành công --}}
    @if ($step === 6)
        <div class="card mx-auto max-w-md text-center">
            <div class="mx-auto mb-3 grid h-18 w-18 place-items-center rounded-full bg-ngoi-brand text-4xl text-ngoi-brand-ink ring-8 ring-ngoi-sel">✓</div>
            <h1 class="font-serif text-3xl">Đặt lịch thành công!</h1>
            <p class="text-ngoi-muted">Vui lòng đến đúng giờ. Hẹn gặp bạn tại Ngói!</p>
            <div class="mx-auto my-4 max-w-xs rounded-xl border-2 border-dashed border-ngoi-brand p-3">
                <div class="text-sm text-ngoi-muted">Mã booking (đưa nhân viên khi đến)</div>
                <div class="font-serif text-3xl font-bold tracking-widest text-ngoi-brand">{{ $resultCode }}</div>
            </div>
            <ul class="mb-3 text-sm">
                @foreach ($this->lines as $l) <li>{{ $l['time'] }} · {{ $l['room'] }}</li> @endforeach
            </ul>
            @if ($qr)
                <p class="text-sm text-ngoi-muted">Muốn trả trước? Quét mã VietQR (nội dung chuyển khoản là mã booking):</p>
                <img src="{{ $qr }}" alt="Mã QR chuyển khoản {{ $resultCode }}" loading="lazy" width="220" height="250" class="mx-auto my-2 rounded-lg">
                @if ($bank)
                    <div class="mx-auto mb-2 flex max-w-xs items-center gap-3 rounded-lg border border-ngoi-line px-3 py-2 text-left text-sm">
                        @if ($bank['logo'])
                            <img src="{{ $bank['logo'] }}" alt="{{ $bank['short_name'] }}" loading="lazy" width="64" height="24" class="h-6 w-16 object-contain">
                        @endif
                        <div>
                            <p class="font-semibold">{{ $bank['short_name'] }} · {{ $bank['account'] }}</p>
                            <p class="text-xs text-ngoi-muted">{{ $bank['account_name'] }}</p>
                        </div>
                    </div>
                @endif
            @endif
            <p class="text-xs text-ngoi-muted">Email xác nhận đã được gửi tới {{ auth()->user()->email }}.</p>
            <div class="mt-4 flex justify-center gap-3">
                <a href="{{ route('my-bookings') }}" wire:navigate class="btn">Xem lịch của tôi</a>
                <button class="btn-ghost" wire:click="restart">Đặt thêm</button>
            </div>
        </div>
    @endif
</div>
