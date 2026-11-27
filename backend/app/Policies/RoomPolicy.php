<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, Room $room): bool
    {
        return $user->isOwner();
    }

    public function delete(User $user, Room $room): bool
    {
        return $user->isOwner();
    }
}
