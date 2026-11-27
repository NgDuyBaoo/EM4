<?php

namespace App\Livewire\Customer;

use App\Exceptions\BookingException;
use App\Models\Branch;
use App\Services\AccessCodeService;
use App\Services\AvailabilityService;
use App\Services\BankDirectory;
use App\Services\BookingService;
use App\Services\VietQr;
use App\Services\WeatherService;
use App\Support\SlotClock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Luồng đặt lịch 6 bước: mã truy cập → chi nhánh → phòng → khung giờ → xác nhận → thành công.
 */
#[Layout('layouts.app')]
#[Title('Đặt lịch – Ngói Photobooth')]
class BookingWizard extends Component
{
    public int $step = 1;

    #[Validate('required|digits:6', as: 'mã truy cập')]
    public string $code = '';

    public ?int $branchId = null;

    /** @var list<int> */
    public array $roomIds = [];

    public ?int $tab = null;

    public ?int $range = null;

    /** @var list<string> "roomId|HH:MM" */
    public array $selected = [];

    #[Validate('required|string|max:80', as: 'tên')]
    public string $name = '';

    #[Validate(['required', 'regex:/^(\+?84|0)[\d\s.\-]{9,12}$/'], as: 'số điện thoại', message: ['regex' => 'Số điện thoại cần 10 số, bắt đầu bằng 0.'])]
    public string $phone = '';

    public ?string $resultCode = null;

    public string $notice = '';

    public function mount(): void
    {
        $this->name = (string) auth()->user()->name;
        $this->phone = (string) auth()->user()->phone;
        // Đã nhập đúng mã hôm nay trong phiên này thì bỏ qua bước 1
        if (session('access_ok') === SlotClock::today()) {
            $this->step = 2;
        }
    }

    public function verifyCode(AccessCodeService $codes): void
    {
        $this->validateOnly('code');
        $key = 'access-code:'.auth()->id();
        if (RateLimiter::tooManyAttempts($key, 20)) {
            $this->addError('code', 'Bạn nhập sai quá nhiều lần. Thử lại sau '.RateLimiter::availableIn($key).' giây.');

            return;
        }
        if (! $codes->verify($this->code)) {
            RateLimiter::hit($key, 600);
            $this->addError('code', 'Mã chưa đúng. Kiểm tra lại mã 6 số hôm nay tại quầy Ngói.');
            $this->code = '';

            return;
        }
        session(['access_ok' => SlotClock::today()]);
        $this->go(2);
    }

    public function chooseBranch(int $id): void
    {
        if ($this->branchId !== $id) {
            $this->branchId = $id;
            $this->roomIds = [];
            $this->selected = [];
        }
    }

    public function toggleRoom(int $id): void
    {
        $this->roomIds = in_array($id, $this->roomIds, true)
            ? array_values(array_diff($this->roomIds, [$id]))
            : [...$this->roomIds, $id];
        $this->selected = array_values(array_filter($this->selected, fn ($k) => in_array((int) explode('|', $k)[0], $this->roomIds, true)));
        $this->tab = $this->roomIds[0] ?? null;
    }

    public function toggleSlot(int $roomId, string $time): void
    {
        $k = "{$roomId}|{$time}";
        $room = collect($this->availability['rooms'])->firstWhere('id', $roomId);
        $slot = collect($room['slots'] ?? [])->firstWhere('time', $time);
        if (($slot['state'] ?? null) !== 'free') {
            return;
        }
        $this->selected = in_array($k, $this->selected, true)
            ? array_values(array_diff($this->selected, [$k]))
            : [...$this->selected, $k];
    }

    public function go(int $step): void
    {
        $this->notice = '';
        if ($step >= 2 && session('access_ok') !== SlotClock::today()) {
            $step = 1; // sang ngày mới: nhập lại mã
            $this->notice = 'Đã sang ngày mới. Vui lòng nhập mã truy cập của hôm nay.';
        }
        $this->step = match (true) {
            $step >= 3 && ! $this->branchId => 2,
            $step >= 4 && ! $this->roomIds => 3,
            $step >= 5 && ! $this->selected => 4,
            default => $step,
        };
        if ($this->step === 4) {
            $this->tab ??= $this->roomIds[0] ?? null;
        }
    }

    public function confirm(BookingService $service): void
    {
        $this->validate(['name' => 'required|string|max:80', 'phone' => ['required', 'regex:/^(\+?84|0)[\d\s.\-]{9,12}$/']]);
        if (session('access_ok') !== SlotClock::today()) {
            $this->go(1);

            return;
        }
        $items = array_map(function ($k) {
            [$roomId, $time] = explode('|', $k);

            return ['room_id' => (int) $roomId, 'time' => $time];
        }, $this->selected);

        try {
            $created = $service->create($this->branch, $items, $this->name, $this->phone, user: auth()->user());
            $this->resultCode = $created->first()->booking_code;
            $this->step = 6;
        } catch (BookingException $e) {
            if (isset($e->details['room_id'])) {
                $this->selected = array_values(array_diff($this->selected, [$e->details['room_id'].'|'.$e->details['time']]));
            }
            unset($this->availability);
            $this->step = $this->selected ? 4 : 4;
            $this->notice = $e->getMessage();
        }
    }

    public function restart(): void
    {
        $this->reset(['branchId', 'roomIds', 'selected', 'resultCode', 'tab', 'range', 'notice']);
        $this->go(2);
    }

    #[Computed(persist: true, seconds: 600)]
    public function branches()
    {
        return Cache::remember('catalog:branches', 600, fn () => Branch::active()->with('activeRooms')->withCount('activeRooms')->get());
    }

    #[Computed]
    public function branch(): ?Branch
    {
        return $this->branches->firstWhere('id', $this->branchId);
    }

    #[Computed]
    public function availability(): array
    {
        return $this->branch ? app(AvailabilityService::class)->forBranch($this->branch) : ['rooms' => [], 'slots' => [], 'date' => SlotClock::today(), 'now' => null];
    }

    /** Các khoảng 2 giờ để chọn giờ cho gọn */
    #[Computed]
    public function ranges(): array
    {
        if (! $this->branch) {
            return [];
        }
        $out = [];
        $open = SlotClock::minutes($this->branch->openAt());
        $close = SlotClock::minutes($this->branch->closeAt());
        for ($m = $open; $m < $close; $m += 120) {
            $out[] = [$m, min($m + 120, $close)];
        }
        if ($this->range === null) {
            $now = SlotClock::nowMinutes();
            $idx = collect($out)->search(fn ($r) => $now < $r[1]);
            $this->range = $idx === false ? 0 : $idx;
        }

        return $out;
    }

    /** @return list<array{room_id:int, room:string, time:string, price:int}> */
    #[Computed]
    public function lines(): array
    {
        $rooms = $this->branch?->activeRooms->keyBy('id') ?? collect();

        return collect($this->selected)->map(function ($k) use ($rooms) {
            [$id, $time] = explode('|', $k);
            $r = $rooms->get((int) $id);

            return ['room_id' => (int) $id, 'room' => $r?->label() ?? '', 'time' => $time, 'price' => (int) $r?->price_per_slot];
        })->sortBy(['room', 'time'])->values()->all();
    }

    public function render()
    {
        return view('livewire.customer.booking-wizard', [
            'weather' => $this->step === 4 ? app(WeatherService::class)->today() : null,
            'qr' => $this->resultCode ? VietQr::imageUrl(array_sum(array_column($this->lines, 'price')), $this->resultCode) : null,
            'bank' => $this->resultCode ? app(BankDirectory::class)->shopAccount() : null,
            'today' => SlotClock::today(),
        ]);
    }
}
