<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ThirdPartyCarePlan;
use Illuminate\Auth\Access\HandlesAuthorization;

class ThirdPartyCarePlanPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ThirdPartyCarePlan');
    }

    public function view(AuthUser $authUser, ThirdPartyCarePlan $thirdPartyCarePlan): bool
    {
        return $authUser->can('View:ThirdPartyCarePlan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ThirdPartyCarePlan');
    }

    public function update(AuthUser $authUser, ThirdPartyCarePlan $thirdPartyCarePlan): bool
    {
        return $authUser->can('Update:ThirdPartyCarePlan');
    }

    public function delete(AuthUser $authUser, ThirdPartyCarePlan $thirdPartyCarePlan): bool
    {
        return $authUser->can('Delete:ThirdPartyCarePlan');
    }

    public function restore(AuthUser $authUser, ThirdPartyCarePlan $thirdPartyCarePlan): bool
    {
        return $authUser->can('Restore:ThirdPartyCarePlan');
    }

    public function forceDelete(AuthUser $authUser, ThirdPartyCarePlan $thirdPartyCarePlan): bool
    {
        return $authUser->can('ForceDelete:ThirdPartyCarePlan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ThirdPartyCarePlan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ThirdPartyCarePlan');
    }

    public function replicate(AuthUser $authUser, ThirdPartyCarePlan $thirdPartyCarePlan): bool
    {
        return $authUser->can('Replicate:ThirdPartyCarePlan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ThirdPartyCarePlan');
    }

}