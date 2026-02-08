<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Enquiry;
use App\Models\User;

final class EnquiryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Enquiry $enquiry): bool
    {
        if ($user->id === $enquiry->user_id) {
            return true;
        }

        return $user->hasTeamRole($user->currentTeam, 'admin') ||
               $user->hasTeamRole($user->currentTeam, 'safeguarding-lead');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Enquiry $enquiry): bool
    {
        if ($user->id === $enquiry->user_id) {
            return true;
        }

        return $user->hasTeamRole($user->currentTeam, 'admin') ||
               $user->hasTeamRole($user->currentTeam, 'safeguarding-lead');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Enquiry $enquiry): bool
    {
        return $user->hasTeamRole($user->currentTeam, 'admin') ||
               $user->hasTeamRole($user->currentTeam, 'safeguarding-lead');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->hasTeamRole($user->currentTeam, 'admin') ||
               $user->hasTeamRole($user->currentTeam, 'safeguarding-lead');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Enquiry $enquiry): bool
    {
        return $user->hasTeamRole($user->currentTeam, 'admin') ||
               $user->hasTeamRole($user->currentTeam, 'safeguarding-lead');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->hasTeamRole($user->currentTeam, 'admin') ||
               $user->hasTeamRole($user->currentTeam, 'safeguarding-lead');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Enquiry $enquiry): bool
    {
        return $user->hasTeamRole($user->currentTeam, 'admin') ||
               $user->hasTeamRole($user->currentTeam, 'safeguarding-lead');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->hasTeamRole($user->currentTeam, 'admin') ||
               $user->hasTeamRole($user->currentTeam, 'safeguarding-lead');
    }
}
