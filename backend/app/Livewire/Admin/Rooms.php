<?php

namespace App\Livewire\Admin;

use App\Models\Branch;
use App\Models\Room;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Chủ cửa hàng: thêm / sửa / xóa phòng, đổi giá, bật tắt. */
#[Layout('layouts.admin')]
#[Title('Phòng & giá – Quản lý Ngói')]
class Rooms extends Component
{
    public ?int $branchId = null;

    public ?int $editingId = null;

    public array $form = ['name' => '', 'style' => '', 'description' => '', 'price_per_slot' => 50000, 'is_active' => true];

    public string $flash = '';

    public function mount(): void
    {
        $this->branchId = Branch::orderBy('sort_order')->value('id');
    }

    public function edit(int $id): void
    {
        $room = Room::findOrFail($id);
        $this->editingId = $id;
        $this->form = $room->only(['name', 'style', 'description', 'price_per_slot', 'is_active']);
    }

    public function create(): void
    {
        $this->editingId = 0;
        $this->form = ['name' => 'Phòng mới', 'style' => '', 'description' => '', 'price_per_slot' => 50000, 'is_active' => true];
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'update' : 'create', $this->editingId ? Room::findOrFail($this->editingId) : Room::class);
        $data = $this->validate([
            'form.name' => ['required', 'string', 'max:80'],
            'form.style' => ['nullable', 'string', 'max:80'],
            'form.description' => ['nullable', 'string', 'max:255'],
            'form.price_per_slot' => ['required', 'integer', 'min:0', 'multiple_of:1000'],
            'form.is_active' => ['boolean'],
        ], [], ['form.name' => 'tên phòng', 'form.price_per_slot' => 'giá mỗi slot'])['form'];
        $data['style'] ??= '';
        $data['description'] ??= '';

        if ($this->editingId) {
            Room::findOrFail($this->editingId)->update($data);
        } else {
            Room::create($data + ['branch_id' => $this->branchId, 'sort_order' => Room::where('branch_id', $this->branchId)->max('sort_order') + 1]);
        }
        $this->flash = 'Đã lưu '.$data['name'].'.';
        $this->editingId = null;
    }

    public function delete(int $id): void
    {
        $room = Room::findOrFail($id);
        $this->authorize('delete', $room);
        $room->delete();
        $this->flash = "Đã xóa {$room->name} (lịch sử booking vẫn giữ).";
    }

    public function render()
    {
        return view('livewire.admin.rooms', [
            'branches' => Branch::orderBy('sort_order')->get(),
            'rooms' => Room::where('branch_id', $this->branchId)->withCount(['bookings' => fn ($q) => $q->where('booking_date', '>=', now()->subDays(30))])->orderBy('sort_order')->get(),
        ]);
    }
}
