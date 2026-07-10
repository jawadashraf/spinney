<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class ServiceUserPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ServiceUser');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:ServiceUser');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ServiceUser');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:ServiceUser');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:ServiceUser');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:ServiceUser');
    }

    public function forceDelete(AuthUser $authUser): bool
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

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:ServiceUser');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ServiceUser');
    }
}
