<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\OutreachEmail;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Outreach emails are for managers and admins (see CompanyPermissions). Only drafts can be changed or sent.
 */
final class OutreachEmailPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:OutreachEmail');
    }

    public function view(AuthUser $authUser, OutreachEmail $outreachEmail): bool
    {
        return $authUser->can('View:OutreachEmail');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:OutreachEmail');
    }

    public function update(AuthUser $authUser, OutreachEmail $outreachEmail): bool
    {
        return $outreachEmail->isDraft() && $authUser->can('Update:OutreachEmail');
    }

    public function delete(AuthUser $authUser, OutreachEmail $outreachEmail): bool
    {
        return $outreachEmail->isDraft() && $authUser->can('Delete:OutreachEmail');
    }

    public function send(AuthUser $authUser, OutreachEmail $outreachEmail): bool
    {
        return $outreachEmail->isDraft() && $authUser->can('Send:OutreachEmail');
    }

    public function replicate(AuthUser $authUser, OutreachEmail $outreachEmail): bool
    {
        return $authUser->can('Create:OutreachEmail');
    }
}
