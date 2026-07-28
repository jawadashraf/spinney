<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Zap\Models\Schedule;

final class SchedulePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Schedule');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:Schedule');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Schedule');
    }

    public function update(AuthUser $authUser, ?Schedule $schedule = null): bool
    {
        if ($schedule && ($schedule->metadata['is_approved'] ?? false)) {
            return $authUser->can('Unlock:Schedule') || ($authUser->can('Update:Schedule') && ($authUser->hasRole('admin') || $authUser->hasRole('manager')));
        }

        return $authUser->can('Update:Schedule');
    }

    public function delete(AuthUser $authUser, ?Schedule $schedule = null): bool
    {
        if ($schedule && ($schedule->metadata['is_approved'] ?? false)) {
            return $authUser->can('Unlock:Schedule') || ($authUser->can('Delete:Schedule') && ($authUser->hasRole('admin') || $authUser->hasRole('manager')));
        }

        return $authUser->can('Delete:Schedule');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:Schedule');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:Schedule');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Schedule');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Schedule');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:Schedule');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Schedule');
    }
}
