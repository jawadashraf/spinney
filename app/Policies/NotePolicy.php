<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Note;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class NotePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('ViewAny:Note');
    }

    public function view(User $user, Note $note): bool
    {
        return $user->checkPermissionTo('View:Note');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('Create:Note');
    }

    public function update(User $user, Note $note): bool
    {
        return $user->checkPermissionTo('Update:Note');
    }

    public function delete(User $user, Note $note): bool
    {
        return $user->checkPermissionTo('Delete:Note');
    }

    public function restore(User $user, Note $note): bool
    {
        return $user->checkPermissionTo('Restore:Note');
    }

    public function forceDelete(User $user, Note $note): bool
    {
        return $user->checkPermissionTo('ForceDelete:Note');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('ForceDeleteAny:Note');
    }

    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('RestoreAny:Note');
    }

    public function replicate(User $user, Note $note): bool
    {
        return $user->checkPermissionTo('Replicate:Note');
    }

    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('Reorder:Note');
    }
}
