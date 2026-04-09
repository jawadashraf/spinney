<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Enquiry;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class EnquiryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('ViewAny:Enquiry');
    }

    public function view(User $user, Enquiry $enquiry): bool
    {
        return $user->checkPermissionTo('View:Enquiry');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('Create:Enquiry');
    }

    public function update(User $user, Enquiry $enquiry): bool
    {
        return $user->checkPermissionTo('Update:Enquiry');
    }

    public function delete(User $user, Enquiry $enquiry): bool
    {
        return $user->checkPermissionTo('Delete:Enquiry');
    }

    public function restore(User $user, Enquiry $enquiry): bool
    {
        return $user->checkPermissionTo('Restore:Enquiry');
    }

    public function forceDelete(User $user, Enquiry $enquiry): bool
    {
        return $user->checkPermissionTo('ForceDelete:Enquiry');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('ForceDeleteAny:Enquiry');
    }

    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('RestoreAny:Enquiry');
    }

    public function replicate(User $user, Enquiry $enquiry): bool
    {
        return $user->checkPermissionTo('Replicate:Enquiry');
    }

    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('Reorder:Enquiry');
    }
}
