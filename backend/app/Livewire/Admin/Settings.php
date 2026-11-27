<?php

namespace App\Livewire\Admin;

use App\Services\AccessCodeService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Cài đặt – Quản lý Ngói')]
class Settings extends Component
{
    public bool $confirmRegen = false;

    public string $currentPassword = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $flash = '';

    public function regenerate(AccessCodeService $codes): void
    {
        $codes->regenerate(auth()->id());
        $this->confirmRegen = false;
        $this->flash = 'Đã tạo mã truy cập mới cho hôm nay.';
    }

    public function changePassword(): void
    {
        $this->validate([
            'currentPassword' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [], ['currentPassword' => 'mật khẩu hiện tại', 'password' => 'mật khẩu mới']);
        auth()->user()->update(['password' => Hash::make($this->password)]);
        $this->reset(['currentPassword', 'password', 'password_confirmation']);
        $this->flash = 'Đã đổi mật khẩu.';
    }

    public function render(AccessCodeService $codes)
    {
        return view('livewire.admin.settings', ['code' => $codes->today()]);
    }
}
