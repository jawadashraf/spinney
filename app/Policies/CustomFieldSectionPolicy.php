<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CustomFieldSection;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class CustomFieldSectionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CustomFieldSection');
    }

    public function view(AuthUser $authUser, CustomFieldSection $customFieldSection): bool
    {
        return $authUser->can('View:CustomFieldSection');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CustomFieldSection');
    }

    public function update(AuthUser $authUser, CustomFieldSection $customFieldSection): bool
    {
        return $authUser->can('Update:CustomFieldSection');
    }

    public function delete(AuthUser $authUser, CustomFieldSection $customFieldSection): bool
    {
        return $authUser->can('Delete:CustomFieldSection');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CustomFieldSection');
    }

    public function restore(AuthUser $authUser, CustomFieldSection $customFieldSection): bool
    {
        return $authUser->can('Restore:CustomFieldSection');
    }

    public function forceDelete(AuthUser $authUser, CustomFieldSection $customFieldSection): bool
    {
        return $authUser->can('ForceDelete:CustomFieldSection');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CustomFieldSection');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CustomFieldSection');
    }

    public function replicate(AuthUser $authUser, CustomFieldSection $customFieldSection): bool
    {
        return $authUser->can('Replicate:CustomFieldSection');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CustomFieldSection');
    }
}
