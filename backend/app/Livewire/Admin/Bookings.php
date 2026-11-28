<?php

namespace App\Livewire\Admin;

use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Livewire\Concerns\RequiresStaff;
use App\Models\Booking;
use App\Models\Branch;
use App\Services\BookingService;
use App\Support\SlotClock;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Quản lý booking – Ngói')]
class Bookings extends Component
{
    use RequiresStaff;
    use WithPagination;

    #[Url]
    public string $status = 'all';

    #[Url]
    public string $date = '';

    #[Url]
    public ?int $branchId = null;

    #[Url(as: 'q')]
    public string $search = '';

    public string $flash = '';

    public ?int $confirming = null;

    public function mount(): void
    {
        $this->date = $this->date ?: SlotClock::today();
    }

    public function updating($prop): void
    {
        if (in_array($prop, ['status', 'date', 'branchId', 'search'])) {
            $this->resetPage();
        }
    }

    public function act(int $id, string $action, BookingService $service): void
    {
        $this->ensureStaff();
        $to = ['checkin' => BookingStatus::CheckedIn, 'complete' => BookingStatus::Completed, 'cancel' => BookingStatus::Cancelled, 'late' => BookingStatus::Cancelled][$action];
        try {
            $b = $service->transition(Booking::findOrFail($id), $to, $action === 'late' ? 'Khách đến trễ' : 'Nhân viên hủy');
            $this->flash = "{$b->booking_code} · {$b->time()}: {$to->label()}.";
        } catch (BookingException $e) {
            $this->flash = $e->getMessage();
        }
        $this->confirming = null;
    }

    public function render()
    {
        $bookings = Booking::query()
            ->with(['room', 'branch'])
            ->when($this->date, fn ($q) => $q->onDate($this->date))
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))
            ->when(trim($this->search) !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('customer_name', 'like', '%'.trim($this->search).'%')
                ->orWhere('customer_phone', 'like', '%'.trim($this->search).'%')
                ->orWhere('booking_code', 'like', '%'.trim($this->search).'%')))
            ->orderByDesc('booking_date')->orderBy('slot_time')
            ->paginate(20);

        return view('livewire.admin.bookings', [
            'bookings' => $bookings,
            'branches' => Branch::orderBy('sort_order')->get(['id', 'name']),
            'statuses' => BookingStatus::cases(),
        ]);
    }
}
