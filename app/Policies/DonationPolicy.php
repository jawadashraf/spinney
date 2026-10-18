<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Donations are for managers and admins (see DonationPermissions). Only admins delete them.
 */
final class DonationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Donation');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:Donation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Donation');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:Donation');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:Donation');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:Donation');
    }
}
