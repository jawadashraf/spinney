<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Note;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class NotePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Note');
    }

    public function view(AuthUser $authUser, Note $note): bool
    {
        return $this->canReach($authUser, $note) && $authUser->can('View:Note');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Note');
    }

    public function update(AuthUser $authUser, Note $note): bool
    {
        return $this->canReach($authUser, $note) && $authUser->can('Update:Note');
    }

    public function delete(AuthUser $authUser, Note $note): bool
    {
        return $this->canReach($authUser, $note) && $authUser->can('Delete:Note');
    }

    public function restore(AuthUser $authUser, Note $note): bool
    {
        return $this->canReach($authUser, $note) && $authUser->can('Restore:Note');
    }

    public function forceDelete(AuthUser $authUser, Note $note): bool
    {
        return $this->canReach($authUser, $note) && $authUser->can('ForceDelete:Note');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Note');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Note');
    }

    public function replicate(AuthUser $authUser, Note $note): bool
    {
        return $this->canReach($authUser, $note) && $authUser->can('Replicate:Note');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Note');
    }

    /**
     * Restricted notes are for managers and admins only (AC NT-002, NT-003), whatever other permissions a user holds.
     */
    private function canReach(AuthUser $authUser, Note $note): bool
    {
        return ! $note->isRestricted() || ($authUser instanceof User && $authUser->canSeeRestrictedNotes());
    }
}
