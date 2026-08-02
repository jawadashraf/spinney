<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class TaskPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Task');
    }

    public function view(AuthUser $authUser, Task $task): bool
    {
        return $this->isAllowedTaskForUser($authUser, $task) && $authUser->can('View:Task');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Task');
    }

    public function update(AuthUser $authUser, Task $task): bool
    {
        return $this->isAllowedTaskForUser($authUser, $task) && $authUser->can('Update:Task');
    }

    public function delete(AuthUser $authUser, Task $task): bool
    {
        return $this->isAllowedTaskForUser($authUser, $task) && $authUser->can('Delete:Task');
    }

    public function restore(AuthUser $authUser, Task $task): bool
    {
        return $this->isAllowedTaskForUser($authUser, $task) && $authUser->can('Restore:Task');
    }

    public function forceDelete(AuthUser $authUser, Task $task): bool
    {
        return $this->isAllowedTaskForUser($authUser, $task) && $authUser->can('ForceDelete:Task');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Task');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Task');
    }

    public function replicate(AuthUser $authUser, Task $task): bool
    {
        return $this->isAllowedTaskForUser($authUser, $task) && $authUser->can('Replicate:Task');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Task');
    }

    public function attachPeople(AuthUser $authUser): bool
    {
        return $authUser->can('AttachPeople:Task');
    }

    private function isAllowedTaskForUser(AuthUser $authUser, Task $task): bool
    {
        if ($authUser instanceof User && $authUser->hasRole('volunteer_liaison')) {
            return $task->creator_id === $authUser->id || $task->assignees()->where('users.id', $authUser->id)->exists();
        }

        return true;
    }
}
