<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class SchedulePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('ViewAny:Schedule');
    }

    public function view(User $user, Schedule $schedule): bool
    {
        return $user->checkPermissionTo('View:Schedule');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('Create:Schedule');
    }

    public function update(User $user, Schedule $schedule): bool
    {
        return $user->checkPermissionTo('Update:Schedule');
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return $user->checkPermissionTo('Delete:Schedule');
    }

    public function restore(User $user, Schedule $schedule): bool
    {
        return $user->checkPermissionTo('Restore:Schedule');
    }

    public function forceDelete(User $user, Schedule $schedule): bool
    {
        return $user->checkPermissionTo('ForceDelete:Schedule');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('ForceDeleteAny:Schedule');
    }

    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('RestoreAny:Schedule');
    }

    public function replicate(User $user, Schedule $schedule): bool
    {
        return $user->checkPermissionTo('Replicate:Schedule');
    }

    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('Reorder:Schedule');
    }
}
