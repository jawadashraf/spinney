<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('ViewAny:User');
    }

    public function view(User $user): bool
    {
        return $user->checkPermissionTo('View:User');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('Create:User');
    }

    public function update(User $user): bool
    {
        return $user->checkPermissionTo('Update:User');
    }

    public function delete(User $user): bool
    {
        return $user->checkPermissionTo('Delete:User');
    }

    public function restore(User $user): bool
    {
        return $user->checkPermissionTo('Restore:User');
    }

    public function forceDelete(User $user): bool
    {
        return $user->checkPermissionTo('ForceDelete:User');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('ForceDeleteAny:User');
    }

    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('RestoreAny:User');
    }

    public function replicate(User $user): bool
    {
        return $user->checkPermissionTo('Replicate:User');
    }

    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('Reorder:User');
    }
}
