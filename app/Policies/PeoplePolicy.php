<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\People;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class PeoplePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:People');
    }

    public function view(AuthUser $authUser, People $people): bool
    {
        return $authUser->can('View:People');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:People');
    }

    public function update(AuthUser $authUser, People $people): bool
    {
        return $authUser->can('Update:People');
    }

    public function delete(AuthUser $authUser, People $people): bool
    {
        return $authUser->can('Delete:People');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:People');
    }

    public function restore(AuthUser $authUser, People $people): bool
    {
        return $authUser->can('Restore:People');
    }

    public function forceDelete(AuthUser $authUser, People $people): bool
    {
        return $authUser->can('ForceDelete:People');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:People');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:People');
    }

    public function replicate(AuthUser $authUser, People $people): bool
    {
        return $authUser->can('Replicate:People');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:People');
    }
}
