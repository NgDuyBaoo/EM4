<?php

namespace App\Livewire\Concerns;

/**
 * Action của component quản trị tự kiểm quyền, không chỉ dựa vào middleware lúc tải trang
 * (request gọi action Livewire đi qua /livewire/update, không qua route /admin).
 */
trait RequiresStaff
{
    protected function ensureStaff(): void
    {
        abort_unless(auth()->user()?->isStaff(), 403, 'Bạn không có quyền thực hiện thao tác này.');
    }
}
