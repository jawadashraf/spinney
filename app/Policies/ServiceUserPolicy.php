<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ServiceUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServiceUserPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ServiceUser');
    }

    public function view(AuthUser $authUser, ServiceUser $serviceUser): bool
    {
        return $authUser->can('View:ServiceUser');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ServiceUser');
    }

    public function update(AuthUser $authUser, ServiceUser $serviceUser): bool
    {
        return $authUser->can('Update:ServiceUser');
    }

    public function delete(AuthUser $authUser, ServiceUser $serviceUser): bool
    {
        return $authUser->can('Delete:ServiceUser');
    }

    public function restore(AuthUser $authUser, ServiceUser $serviceUser): bool
    {
        return $authUser->can('Restore:ServiceUser');
    }

    public function forceDelete(AuthUser $authUser, ServiceUser $serviceUser): bool
    {
        return $authUser->can('ForceDelete:ServiceUser');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ServiceUser');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ServiceUser');
    }

    public function replicate(AuthUser $authUser, ServiceUser $serviceUser): bool
    {
        return $authUser->can('Replicate:ServiceUser');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ServiceUser');
    }

}