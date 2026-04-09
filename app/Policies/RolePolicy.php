<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('ViewAny:Role');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->checkPermissionTo('View:Role');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('Create:Role');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->checkPermissionTo('Update:Role');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->checkPermissionTo('Delete:Role');
    }

    public function restore(User $user, Role $role): bool
    {
        return $user->checkPermissionTo('Restore:Role');
    }

    public function forceDelete(User $user, Role $role): bool
    {
        return $user->checkPermissionTo('ForceDelete:Role');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('ForceDeleteAny:Role');
    }

    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('RestoreAny:Role');
    }

    public function replicate(User $user, Role $role): bool
    {
        return $user->checkPermissionTo('Replicate:Role');
    }

    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('Reorder:Role');
    }
}
