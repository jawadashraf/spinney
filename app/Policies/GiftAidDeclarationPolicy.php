<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GiftAidDeclaration;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Gift Aid declarations are for managers and admins (see DonationPermissions). A declaration with donations
 * linked to it is kept for HMRC, so it can be withdrawn but not deleted.
 */
final class GiftAidDeclarationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:GiftAidDeclaration');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:GiftAidDeclaration');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:GiftAidDeclaration');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:GiftAidDeclaration');
    }

    public function delete(AuthUser $authUser, GiftAidDeclaration $giftAidDeclaration): bool
    {
        return $authUser->can('Delete:GiftAidDeclaration') && ! $giftAidDeclaration->donations()->exists();
    }
}
