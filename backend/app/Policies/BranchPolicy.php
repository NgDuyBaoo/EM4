<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->isOwner();
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->isOwner();
    }
}
