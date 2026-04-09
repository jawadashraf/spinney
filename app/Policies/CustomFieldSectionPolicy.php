<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CustomFieldSection;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class CustomFieldSectionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('ViewAny:CustomFieldSection');
    }

    public function view(User $user, CustomFieldSection $customFieldSection): bool
    {
        return $user->checkPermissionTo('View:CustomFieldSection');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('Create:CustomFieldSection');
    }

    public function update(User $user, CustomFieldSection $customFieldSection): bool
    {
        return $user->checkPermissionTo('Update:CustomFieldSection');
    }

    public function delete(User $user, CustomFieldSection $customFieldSection): bool
    {
        return $user->checkPermissionTo('Delete:CustomFieldSection');
    }

    public function restore(User $user, CustomFieldSection $customFieldSection): bool
    {
        return $user->checkPermissionTo('Restore:CustomFieldSection');
    }

    public function forceDelete(User $user, CustomFieldSection $customFieldSection): bool
    {
        return $user->checkPermissionTo('ForceDelete:CustomFieldSection');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('ForceDeleteAny:CustomFieldSection');
    }

    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('RestoreAny:CustomFieldSection');
    }

    public function replicate(User $user, CustomFieldSection $customFieldSection): bool
    {
        return $user->checkPermissionTo('Replicate:CustomFieldSection');
    }

    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('Reorder:CustomFieldSection');
    }
}
