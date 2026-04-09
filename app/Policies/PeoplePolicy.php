<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\People;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class PeoplePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('ViewAny:People');
    }

    public function view(User $user, People $people): bool
    {
        return $user->checkPermissionTo('View:People');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('Create:People');
    }

    public function update(User $user, People $people): bool
    {
        return $user->checkPermissionTo('Update:People');
    }

    public function delete(User $user, People $people): bool
    {
        return $user->checkPermissionTo('Delete:People');
    }

    public function restore(User $user, People $people): bool
    {
        return $user->checkPermissionTo('Restore:People');
    }

    public function forceDelete(User $user, People $people): bool
    {
        return $user->checkPermissionTo('ForceDelete:People');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('ForceDeleteAny:People');
    }

    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('RestoreAny:People');
    }

    public function replicate(User $user, People $people): bool
    {
        return $user->checkPermissionTo('Replicate:People');
    }

    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('Reorder:People');
    }
}
