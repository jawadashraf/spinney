<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ReferralOrganisation;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class ReferralOrganisationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ReferralOrganisation');
    }

    public function view(AuthUser $authUser, ?ReferralOrganisation $organisation = null): bool
    {
        return $authUser->can('View:ReferralOrganisation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ReferralOrganisation');
    }

    public function update(AuthUser $authUser, ?ReferralOrganisation $organisation = null): bool
    {
        return $authUser->can('Update:ReferralOrganisation');
    }

    public function delete(AuthUser $authUser, ?ReferralOrganisation $organisation = null): bool
    {
        return $authUser->can('Delete:ReferralOrganisation');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ReferralOrganisation');
    }

    public function restore(AuthUser $authUser, ?ReferralOrganisation $organisation = null): bool
    {
        return $authUser->can('Restore:ReferralOrganisation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ReferralOrganisation');
    }

    public function forceDelete(AuthUser $authUser, ?ReferralOrganisation $organisation = null): bool
    {
        return $authUser->can('ForceDelete:ReferralOrganisation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ReferralOrganisation');
    }
}
