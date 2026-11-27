<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // khách chỉ thấy booking của mình (lọc ở controller)
    }

    public function view(User $user, Booking $booking): bool
    {
        return $user->isStaff() || $booking->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    /** Đổi trạng thái / sửa thông tin: nhân viên. Khách chỉ được hủy lịch của mình. */
    public function update(User $user, Booking $booking): bool
    {
        return $user->isStaff() || $booking->user_id === $user->id;
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $user->isOwner();
    }
}
