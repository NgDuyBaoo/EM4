@php($S = \App\Enums\BookingStatus::class)
@if ($b->status === $S::Booked)
    <button class="btn btn-sm" wire:click="act({{ $b->id }}, 'checkin')">Check-in</button>
@endif
@if ($b->status === $S::CheckedIn)
    <button class="btn btn-sm" wire:click="act({{ $b->id }}, 'complete')">Hoàn thành</button>
@endif
@if ($b->status->isActive() && $b->status !== $S::Completed)
    <button class="btn-ghost btn-sm" wire:click="act({{ $b->id }}, 'cancel')" wire:confirm="Hủy booking {{ $b->booking_code }} lúc {{ $b->time() }}?">Hủy</button>
    @if ($b->status === $S::Booked)
        <button class="btn-ghost btn-sm" wire:click="act({{ $b->id }}, 'late')" wire:confirm="Hủy vì khách đến trễ?">Hủy vì trễ giờ</button>
    @endif
@endif
