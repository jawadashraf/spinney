<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class CompanyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('ViewAny:Company');
    }

    public function view(User $user, Company $company): bool
    {
        return $user->checkPermissionTo('View:Company');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('Create:Company');
    }

    public function update(User $user, Company $company): bool
    {
        return $user->checkPermissionTo('Update:Company');
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->checkPermissionTo('Delete:Company');
    }

    public function restore(User $user, Company $company): bool
    {
        return $user->checkPermissionTo('Restore:Company');
    }

    public function forceDelete(User $user, Company $company): bool
    {
        return $user->checkPermissionTo('ForceDelete:Company');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('ForceDeleteAny:Company');
    }

    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('RestoreAny:Company');
    }

    public function replicate(User $user, Company $company): bool
    {
        return $user->checkPermissionTo('Replicate:Company');
    }

    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('Reorder:Company');
    }
}
