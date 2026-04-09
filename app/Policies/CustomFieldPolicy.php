<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CustomField;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class CustomFieldPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('ViewAny:CustomField');
    }

    public function view(User $user, CustomField $customField): bool
    {
        return $user->checkPermissionTo('View:CustomField');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('Create:CustomField');
    }

    public function update(User $user, CustomField $customField): bool
    {
        return $user->checkPermissionTo('Update:CustomField');
    }

    public function delete(User $user, CustomField $customField): bool
    {
        return $user->checkPermissionTo('Delete:CustomField');
    }

    public function restore(User $user, CustomField $customField): bool
    {
        return $user->checkPermissionTo('Restore:CustomField');
    }

    public function forceDelete(User $user, CustomField $customField): bool
    {
        return $user->checkPermissionTo('ForceDelete:CustomField');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('ForceDeleteAny:CustomField');
    }

    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('RestoreAny:CustomField');
    }

    public function replicate(User $user, CustomField $customField): bool
    {
        return $user->checkPermissionTo('Replicate:CustomField');
    }

    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('Reorder:CustomField');
    }
}
