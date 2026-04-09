<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class TaskPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('ViewAny:Task');
    }

    public function view(User $user, Task $task): bool
    {
        return $user->checkPermissionTo('View:Task');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('Create:Task');
    }

    public function update(User $user, Task $task): bool
    {
        return $user->checkPermissionTo('Update:Task');
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->checkPermissionTo('Delete:Task');
    }

    public function restore(User $user, Task $task): bool
    {
        return $user->checkPermissionTo('Restore:Task');
    }

    public function forceDelete(User $user, Task $task): bool
    {
        return $user->checkPermissionTo('ForceDelete:Task');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('ForceDeleteAny:Task');
    }

    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('RestoreAny:Task');
    }

    public function replicate(User $user, Task $task): bool
    {
        return $user->checkPermissionTo('Replicate:Task');
    }

    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('Reorder:Task');
    }
}
