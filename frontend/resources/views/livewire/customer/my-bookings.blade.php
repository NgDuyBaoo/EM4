<div>
    <h1 class="font-serif text-3xl">Lịch của tôi</h1>
    <p class="mb-4 text-ngoi-muted">Bận thì bạn có thể hủy trước giờ chụp.</p>
    @if ($flash) <div class="card mb-3" role="status">{{ $flash }}</div> @endif

    <div class="card overflow-x-auto p-2">
        <table class="w-full text-sm">
            <thead class="text-left text-ngoi-muted">
                <tr><th class="p-2">Ngày</th><th class="p-2">Giờ</th><th class="p-2">Phòng</th><th class="p-2">Mã</th><th class="p-2">Giá</th><th class="p-2">Trạng thái</th><th class="p-2"></th></tr>
            </thead>
            <tbody>
                @forelse ($bookings as $b)
                    <tr class="border-t border-ngoi-line" wire:key="b-{{ $b->id }}">
                        <td class="p-2">{{ $b->booking_date->format('d/m') }}</td>
                        <td class="p-2 font-semibold">{{ $b->time() }}</td>
                        <td class="p-2">{{ $b->room->label() }}<span class="text-ngoi-muted"> · {{ $b->branch->name }}</span></td>
                        <td class="p-2">{{ $b->booking_code }}</td>
                        <td class="p-2">{{ number_format($b->price, 0, ',', '.') }}đ</td>
                        <td class="p-2"><x-status-pill :status="$b->status" /></td>
                        <td class="p-2 text-right">
                            @if ($b->status === \App\Enums\BookingStatus::Booked && ! \App\Support\SlotClock::started($b->time(), $b->booking_date->toDateString()))
                                @if ($confirming === $b->id)
                                    <button class="btn btn-sm btn-danger" wire:click="cancel({{ $b->id }})">Xác nhận hủy</button>
                                    <button class="btn-ghost btn-sm" wire:click="$set('confirming', null)">Giữ lịch</button>
                                @else
                                    <button class="btn-ghost btn-sm" wire:click="$set('confirming', {{ $b->id }})">Hủy lịch</button>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-4 text-center text-ngoi-muted">Bạn chưa có lịch nào. <a class="text-ngoi-brand underline" href="{{ route('booking') }}">Đặt lịch ngay</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $bookings->links() }}</div>
</div>
