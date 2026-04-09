<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Activitylog\Models\Activity;

final class ActivityPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('ViewAny:Activity');
    }

    public function view(User $user, Activity $activity): bool
    {
        return $user->checkPermissionTo('View:Activity');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('Create:Activity');
    }

    public function update(User $user, Activity $activity): bool
    {
        return $user->checkPermissionTo('Update:Activity');
    }

    public function delete(User $user, Activity $activity): bool
    {
        return $user->checkPermissionTo('Delete:Activity');
    }

    public function restore(User $user, Activity $activity): bool
    {
        return $user->checkPermissionTo('Restore:Activity');
    }

    public function forceDelete(User $user, Activity $activity): bool
    {
        return $user->checkPermissionTo('ForceDelete:Activity');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('ForceDeleteAny:Activity');
    }

    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('RestoreAny:Activity');
    }

    public function replicate(User $user, Activity $activity): bool
    {
        return $user->checkPermissionTo('Replicate:Activity');
    }

    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('Reorder:Activity');
    }
}
