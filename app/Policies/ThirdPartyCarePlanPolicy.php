<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ThirdPartyCarePlan;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class ThirdPartyCarePlanPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('ViewAny:ThirdPartyCarePlan');
    }

    public function view(User $user, ThirdPartyCarePlan $thirdPartyCarePlan): bool
    {
        return $user->checkPermissionTo('View:ThirdPartyCarePlan');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('Create:ThirdPartyCarePlan');
    }

    public function update(User $user, ThirdPartyCarePlan $thirdPartyCarePlan): bool
    {
        return $user->checkPermissionTo('Update:ThirdPartyCarePlan');
    }

    public function delete(User $user, ThirdPartyCarePlan $thirdPartyCarePlan): bool
    {
        return $user->checkPermissionTo('Delete:ThirdPartyCarePlan');
    }

    public function restore(User $user, ThirdPartyCarePlan $thirdPartyCarePlan): bool
    {
        return $user->checkPermissionTo('Restore:ThirdPartyCarePlan');
    }

    public function forceDelete(User $user, ThirdPartyCarePlan $thirdPartyCarePlan): bool
    {
        return $user->checkPermissionTo('ForceDelete:ThirdPartyCarePlan');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('ForceDeleteAny:ThirdPartyCarePlan');
    }

    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('RestoreAny:ThirdPartyCarePlan');
    }

    public function replicate(User $user, ThirdPartyCarePlan $thirdPartyCarePlan): bool
    {
        return $user->checkPermissionTo('Replicate:ThirdPartyCarePlan');
    }

    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('Reorder:ThirdPartyCarePlan');
    }
}
