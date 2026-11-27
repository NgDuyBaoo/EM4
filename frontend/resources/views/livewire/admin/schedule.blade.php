<div wire:poll.30s="refreshGrid">
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-serif text-3xl">Lịch đặt phòng</h1>
            <p class="text-ngoi-muted">{{ \Illuminate\Support\Carbon::parse($date)->locale('vi')->isoFormat('dddd, DD/MM/YYYY') }} · {{ $this->branch->name }} · {{ $this->branch->openAt() }}–{{ $this->branch->closeAt() }}</p>
        </div>
        <div class="card px-4 py-2"><span class="text-xs text-ngoi-muted">Mã truy cập hôm nay</span><br><span class="font-serif text-2xl font-bold tracking-[.15em] text-ngoi-brand">{{ $accessCode }}</span></div>
    </div>

    <div class="mb-4 flex flex-wrap items-end gap-3">
        <div><label class="label" for="br">Chi nhánh</label>
            <select id="br" wire:model.live="branchId" class="field">@foreach ($this->branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
        <div><label class="label" for="dt">Ngày</label><input id="dt" type="date" wire:model.live="date" class="field"></div>
        <div><label class="label" for="wd">Khoảng giờ</label>
            <select id="wd" wire:model.live="window" class="field">@foreach ($windows as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></div>
        @if ($isToday)<label class="flex items-center gap-2 pb-3 text-sm"><input type="checkbox" wire:model.live="hidePast" class="accent-ngoi-brand"> Ẩn giờ đã qua</label>@endif
    </div>

    @if ($flash) <div class="card mb-3 py-3" role="status">{{ $flash }}</div> @endif

    <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach (\App\Enums\BookingStatus::cases() as $s)
            <div class="card py-4"><b class="block font-serif text-3xl">{{ $this->counts[$s->value] }}</b><span class="text-sm text-ngoi-muted">{{ $s === \App\Enums\BookingStatus::Booked ? 'Chờ check-in' : $s->label() }}</span></div>
        @endforeach
    </div>

    <div class="mb-2 flex flex-wrap gap-4 text-sm">
        <span><i class="mr-1 inline-block h-3 w-3 rounded bg-ngoi-free align-[-1px]"></i>Trống (bấm để đặt tại quầy)</span>
        @foreach ([\App\Enums\BookingStatus::Booked, \App\Enums\BookingStatus::CheckedIn, \App\Enums\BookingStatus::Completed] as $s)
            <span><i class="mr-1 inline-block h-3 w-3 rounded align-[-1px] {{ $s->badge() }}"></i>{{ $s->label() }}</span>
        @endforeach
    </div>

    <div class="max-h-[70vh] overflow-auto rounded-2xl border border-ngoi-line bg-ngoi-surface">
        <table class="w-full text-sm">
            <thead class="sticky top-0 z-10 bg-ngoi-surface">
                <tr>
                    <th class="p-2 text-left text-ngoi-muted">Giờ</th>
                    @foreach ($this->grid['rooms'] as $r)<th class="p-2 text-left">{{ $r['name'] }}<br><span class="text-xs font-normal text-ngoi-muted">{{ $r['style'] }}</span></th>@endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($times as $t)
                    <tr class="border-t border-ngoi-line" wire:key="row-{{ $t }}">
                        <td class="sticky left-0 bg-ngoi-surface p-2 font-semibold">{{ $t }}</td>
                        @foreach ($this->grid['rooms'] as $r)
                            @php($b = $this->cells->get($r['id'].'|'.$t))
                            <td class="p-1">
                                @if ($b)
                                    <button wire:click="$set('detailId', {{ $b->id }})" class="h-8 w-full min-w-32 truncate rounded-md px-2 text-left {{ $b->status->badge() }}" title="{{ $b->customer_name }} · {{ $b->status->label() }}">{{ $b->customer_name }}</button>
                                @elseif ($isPastDay || ($isToday && \App\Support\SlotClock::ended($t)))
                                    <span class="block h-8 rounded-md border border-dashed border-ngoi-line text-center leading-8 text-ngoi-muted">Đã qua</span>
                                @elseif ($isToday)
                                    <button wire:click="openWalkin({{ $r['id'] }}, '{{ $t }}')" class="h-8 w-full min-w-32 rounded-md bg-ngoi-free/60 hover:bg-ngoi-free" title="Đặt tại quầy · {{ $r['name'] }} · {{ $t }}" aria-label="Đặt tại quầy {{ $r['name'] }} {{ $t }}">+</button>
                                @else
                                    <span class="block h-8 rounded-md bg-ngoi-free/40 text-center leading-8 text-ngoi-muted">Trống</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="9" class="p-4 text-ngoi-muted">Không còn khung giờ trong khoảng đang xem.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Chi tiết booking --}}
    @if ($this->detail)
        @php($d = $this->detail)
        <div class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4" role="dialog" aria-modal="true" wire:click.self="$set('detailId', null)">
            <div class="card w-full max-w-md">
                <div class="mb-3 flex items-center justify-between"><h2 class="font-serif text-xl">Booking {{ $d->booking_code }}</h2><button class="btn-ghost btn-sm" wire:click="$set('detailId', null)">Đóng</button></div>
                <dl class="grid grid-cols-[100px_1fr] gap-2 text-sm">
                    <dt class="text-ngoi-muted">Khách</dt><dd>{{ $d->customer_name }}</dd>
                    <dt class="text-ngoi-muted">SĐT</dt><dd><a class="underline" href="tel:{{ $d->customer_phone }}">{{ $d->customer_phone }}</a></dd>
                    <dt class="text-ngoi-muted">Phòng</dt><dd>{{ $d->room->label() }}</dd>
                    <dt class="text-ngoi-muted">Giờ</dt><dd>{{ $d->time() }} · {{ $d->booking_date->format('d/m/Y') }}</dd>
                    <dt class="text-ngoi-muted">Giá</dt><dd>{{ number_format($d->price, 0, ',', '.') }}đ</dd>
                    <dt class="text-ngoi-muted">Nguồn</dt><dd>{{ $d->source->label() }}</dd>
                    <dt class="text-ngoi-muted">Trạng thái</dt><dd><x-status-pill :status="$d->status" /></dd>
                </dl>
                <div class="mt-4 flex flex-wrap gap-2">
                    @include('livewire.admin.partials.actions', ['b' => $d])
                </div>
            </div>
        </div>
    @endif

    {{-- Đặt tại quầy --}}
    @if ($walkin)
        @php($wr = collect($this->grid['rooms'])->firstWhere('id', $walkin['room_id']))
        <div class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4" role="dialog" aria-modal="true">
            <form wire:submit="saveWalkin" class="card w-full max-w-md">
                <div class="mb-3 flex items-center justify-between"><h2 class="font-serif text-xl">Đặt lịch tại quầy</h2><button type="button" class="btn-ghost btn-sm" wire:click="$set('walkin', null)">Đóng</button></div>
                <p class="mb-3 text-ngoi-muted">{{ $wr['name'] }} · {{ $wr['style'] }} · {{ $walkin['time'] }} · {{ number_format($wr['price_per_slot'], 0, ',', '.') }}đ</p>
                <label class="label" for="wn">Tên khách</label><input id="wn" wire:model="walkName" class="field" autofocus>
                @error('walkName') <p class="error">{{ $message }}</p> @enderror
                <label class="label mt-3" for="wp">Số điện thoại</label><input id="wp" wire:model="walkPhone" inputmode="tel" class="field">
                @error('walkPhone') <p class="error">{{ $message }}</p> @enderror
                <button class="btn mt-4 w-full">Tạo booking</button>
            </form>
        </div>
    @endif
</div>
