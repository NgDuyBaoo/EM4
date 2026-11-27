<?php

namespace App\Livewire\Customer;

use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Services\BookingService;
use App\Support\SlotClock;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Lịch của tôi – Ngói Photobooth')]
class MyBookings extends Component
{
    use WithPagination;

    public ?int $confirming = null;

    public string $flash = '';

    public function cancel(int $id, BookingService $service): void
    {
        $booking = Booking::where('user_id', auth()->id())->findOrFail($id);
        try {
            $service->customerCancel($booking);
            $this->flash = "Đã hủy lịch {$booking->time()}.";
        } catch (BookingException $e) {
            $this->flash = $e->getMessage();
        }
        $this->confirming = null;
    }

    public function render()
    {
        $bookings = Booking::query()
            ->with(['room', 'branch'])
            ->where('user_id', auth()->id())
            ->orderByDesc('booking_date')->orderBy('slot_time')
            ->paginate(15);

        return view('livewire.customer.my-bookings', ['bookings' => $bookings, 'today' => SlotClock::today()]);
    }
}
