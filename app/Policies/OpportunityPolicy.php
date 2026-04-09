<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class OpportunityPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('ViewAny:Opportunity');
    }

    public function view(User $user, Opportunity $opportunity): bool
    {
        return $user->checkPermissionTo('View:Opportunity');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('Create:Opportunity');
    }

    public function update(User $user, Opportunity $opportunity): bool
    {
        return $user->checkPermissionTo('Update:Opportunity');
    }

    public function delete(User $user, Opportunity $opportunity): bool
    {
        return $user->checkPermissionTo('Delete:Opportunity');
    }

    public function restore(User $user, Opportunity $opportunity): bool
    {
        return $user->checkPermissionTo('Restore:Opportunity');
    }

    public function forceDelete(User $user, Opportunity $opportunity): bool
    {
        return $user->checkPermissionTo('ForceDelete:Opportunity');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('ForceDeleteAny:Opportunity');
    }

    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('RestoreAny:Opportunity');
    }

    public function replicate(User $user, Opportunity $opportunity): bool
    {
        return $user->checkPermissionTo('Replicate:Opportunity');
    }

    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('Reorder:Opportunity');
    }
}
