<?php

namespace App\Livewire\Admin;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Livewire\Concerns\RequiresStaff;
use App\Models\Booking;
use App\Models\Branch;
use App\Services\AccessCodeService;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Support\SlotClock;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Lưới lịch phòng theo giờ: xem trạng thái, bấm ô có khách để xử lý, bấm ô trống để đặt tại quầy. */
#[Layout('layouts.admin')]
#[Title('Lịch phòng – Quản lý Ngói')]
class Schedule extends Component
{
    use RequiresStaff;

    #[Url]
    public ?int $branchId = null;

    #[Url]
    public string $date = '';

    public string $window = 'all';

    public bool $hidePast = true;

    public ?int $detailId = null;

    /** @var array{room_id:int, time:string}|null */
    public ?array $walkin = null;

    public string $walkName = '';

    public string $walkPhone = '';

    public string $flash = '';

    public function mount(): void
    {
        $this->branchId ??= Branch::active()->value('id');
        $this->date = $this->date ?: SlotClock::today();
    }

    #[Computed]
    public function branches()
    {
        return Branch::orderBy('sort_order')->get();
    }

    #[Computed]
    public function branch(): Branch
    {
        return $this->branches->firstWhere('id', $this->branchId) ?? $this->branches->first();
    }

    #[Computed]
    public function grid(): array
    {
        return app(AvailabilityService::class)->forBranch($this->branch, $this->date);
    }

    /** Booking còn hiệu lực theo ô "roomId|HH:MM" – 1 truy vấn, eager load phòng. */
    #[Computed]
    public function cells()
    {
        return Booking::query()->active()->onDate($this->date)->where('branch_id', $this->branch->id)
            ->get()->keyBy(fn (Booking $b) => $b->room_id.'|'.$b->time());
    }

    #[Computed]
    public function counts(): array
    {
        $rows = Booking::query()->onDate($this->date)->where('branch_id', $this->branch->id)
            ->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return collect(BookingStatus::cases())->mapWithKeys(fn ($s) => [$s->value => (int) ($rows[$s->value] ?? 0)])->all();
    }

    #[Computed]
    public function detail(): ?Booking
    {
        return $this->detailId ? Booking::with(['room', 'branch'])->find($this->detailId) : null;
    }

    public function act(int $id, string $action, BookingService $service): void
    {
        $this->ensureStaff();
        $to = match ($action) {
            'checkin' => BookingStatus::CheckedIn,
            'complete' => BookingStatus::Completed,
            'cancel' => BookingStatus::Cancelled,
            'late' => BookingStatus::Cancelled,
        };
        try {
            $service->transition(Booking::findOrFail($id), $to, $action === 'late' ? 'Khách đến trễ' : 'Nhân viên hủy');
            $this->flash = 'Đã cập nhật: '.$to->label().'.';
        } catch (BookingException $e) {
            $this->flash = $e->getMessage();
        }
        $this->detailId = null;
        $this->refreshGrid();
    }

    public function openWalkin(int $roomId, string $time): void
    {
        $this->ensureStaff();
        $this->walkin = ['room_id' => $roomId, 'time' => $time];
        $this->reset(['walkName', 'walkPhone']);
        $this->resetValidation();
    }

    public function saveWalkin(BookingService $service): void
    {
        $this->ensureStaff();
        $this->validate([
            'walkName' => 'required|string|max:80',
            'walkPhone' => ['required', 'regex:/^(\+?84|0)[\d\s.\-]{9,12}$/'],
        ], [], ['walkName' => 'tên khách', 'walkPhone' => 'số điện thoại']);
        try {
            $service->create($this->branch, [$this->walkin], $this->walkName, $this->walkPhone, BookingSource::Walkin);
            $this->flash = 'Đã tạo booking tại quầy.';
            $this->walkin = null;
        } catch (BookingException $e) {
            $this->addError('walkPhone', $e->getMessage());
        }
        $this->refreshGrid();
    }

    public function updated($prop): void
    {
        if (in_array($prop, ['branchId', 'date'])) {
            $this->refreshGrid();
        }
    }

    public function refreshGrid(): void
    {
        unset($this->grid, $this->cells, $this->counts, $this->detail);
    }

    public function render()
    {
        $isToday = $this->date === SlotClock::today();
        $times = collect($this->grid['slots'])->filter(function ($t) use ($isToday) {
            $m = SlotClock::minutes($t);
            if ($this->window !== 'all' && ($m < SlotClock::minutes($this->window) || $m >= SlotClock::minutes($this->window) + 120)) {
                return false;
            }

            return ! ($this->hidePast && $isToday && SlotClock::ended($t));
        })->values();

        $windows = ['all' => 'Cả ngày'];
        $close = SlotClock::minutes($this->branch->closeAt());
        for ($m = SlotClock::minutes($this->branch->openAt()); $m < $close; $m += 120) {
            $windows[SlotClock::time($m)] = SlotClock::time($m).'–'.SlotClock::time(min($m + 120, $close));
        }

        return view('livewire.admin.schedule', [
            'times' => $times,
            'windows' => $windows,
            'isToday' => $isToday,
            'isPastDay' => $this->date < SlotClock::today(),
            'accessCode' => app(AccessCodeService::class)->today()->code,
        ]);
    }
}
