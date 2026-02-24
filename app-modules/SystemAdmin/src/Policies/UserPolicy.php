<?php

declare(strict_types=1);

namespace Relaticle\SystemAdmin\Policies;

use App\Models\User;

final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_system_admin;
    }

    public function view(User $user): bool
    {
        return $user->is_system_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_system_admin;
    }

    public function update(User $user): bool
    {
        return $user->is_system_admin;
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        return $user->is_system_admin;
    }

    public function deleteAny(User $user): bool
    {
        return $user->is_system_admin;
    }

    public function restore(User $user): bool
    {
        return $user->is_system_admin;
    }

    public function forceDelete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        return $user->is_system_admin;
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->is_system_admin;
    }

    public function restoreAny(User $user): bool
    {
        return $user->is_system_admin;
    }
}
