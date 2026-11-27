<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Staff = 'staff';
    case Owner = 'owner';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Khách hàng',
            self::Staff => 'Nhân viên',
            self::Owner => 'Chủ cửa hàng',
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::Customer;
    }
}
