<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class TeamPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('ViewAny:Team');
    }

    public function view(User $user, Team $team): bool
    {
        return $user->checkPermissionTo('View:Team');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('Create:Team');
    }

    public function update(User $user, Team $team): bool
    {
        return $user->checkPermissionTo('Update:Team');
    }

    public function delete(User $user, Team $team): bool
    {
        return $user->checkPermissionTo('Delete:Team');
    }

    public function restore(User $user, Team $team): bool
    {
        return $user->checkPermissionTo('Restore:Team');
    }

    public function forceDelete(User $user, Team $team): bool
    {
        return $user->checkPermissionTo('ForceDelete:Team');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('ForceDeleteAny:Team');
    }

    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('RestoreAny:Team');
    }

    public function replicate(User $user, Team $team): bool
    {
        return $user->checkPermissionTo('Replicate:Team');
    }

    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('Reorder:Team');
    }
}
