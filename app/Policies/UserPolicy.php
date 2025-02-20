<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->isAdmin();
    }

    public function updateRole(User $admin, User $targetUser)
    {
        return $admin->isAdmin() && $admin->id !== $targetUser->id;
    }
}
