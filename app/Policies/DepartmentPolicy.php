<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Department;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class DepartmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('ViewAny:Department');
    }

    public function view(User $user, Department $department): bool
    {
        return $user->checkPermissionTo('View:Department');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('Create:Department');
    }

    public function update(User $user, Department $department): bool
    {
        return $user->checkPermissionTo('Update:Department');
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->checkPermissionTo('Delete:Department');
    }

    public function restore(User $user, Department $department): bool
    {
        return $user->checkPermissionTo('Restore:Department');
    }

    public function forceDelete(User $user, Department $department): bool
    {
        return $user->checkPermissionTo('ForceDelete:Department');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('ForceDeleteAny:Department');
    }

    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('RestoreAny:Department');
    }

    public function replicate(User $user, Department $department): bool
    {
        return $user->checkPermissionTo('Replicate:Department');
    }

    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('Reorder:Department');
    }
}
