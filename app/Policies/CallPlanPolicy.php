<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CallPlan;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class CallPlanPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CallPlan');
    }

    public function view(AuthUser $authUser, CallPlan $callPlan): bool
    {
        return $authUser->can('View:CallPlan') && $this->canAccessPlan($authUser, $callPlan);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CallPlan');
    }

    public function update(AuthUser $authUser, CallPlan $callPlan): bool
    {
        return $authUser->can('Update:CallPlan') && $this->canAccessPlan($authUser, $callPlan);
    }

    public function delete(AuthUser $authUser, CallPlan $callPlan): bool
    {
        return $authUser->can('Delete:CallPlan') && $this->canAccessPlan($authUser, $callPlan);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CallPlan');
    }

    public function restore(AuthUser $authUser, CallPlan $callPlan): bool
    {
        return $authUser->can('Restore:CallPlan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CallPlan');
    }

    public function forceDelete(AuthUser $authUser, CallPlan $callPlan): bool
    {
        return $authUser->can('ForceDelete:CallPlan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CallPlan');
    }

    private function canAccessPlan(AuthUser $authUser, CallPlan $callPlan): bool
    {
        if (! $authUser instanceof User) {
            return false;
        }

        return CallPlan::query()->visibleTo($authUser)->whereKey($callPlan->getKey())->exists();
    }
}
