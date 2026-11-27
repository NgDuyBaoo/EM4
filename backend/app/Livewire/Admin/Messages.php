<?php

namespace App\Livewire\Admin;

use App\Models\Message;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Lời nhắn – Quản lý Ngói')]
class Messages extends Component
{
    use WithPagination;

    public function toggle(int $id): void
    {
        $m = Message::findOrFail($id);
        $m->update(['read_at' => $m->read_at ? null : now()]);
    }

    public function render()
    {
        return view('livewire.admin.messages', ['messages' => Message::with('branch')->latest()->paginate(10)]);
    }
}
