<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Call;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class CallPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Call');
    }

    public function view(AuthUser $authUser, Call $call): bool
    {
        return $authUser->can('View:Call') && $this->canAccessCall($authUser, $call);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Call');
    }

    public function update(AuthUser $authUser, Call $call): bool
    {
        return $authUser->can('Update:Call') && $this->canAccessCall($authUser, $call);
    }

    public function delete(AuthUser $authUser, Call $call): bool
    {
        return $authUser->can('Delete:Call') && $this->canAccessCall($authUser, $call);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Call');
    }

    public function restore(AuthUser $authUser, Call $call): bool
    {
        return $authUser->can('Restore:Call') && $this->canAccessCall($authUser, $call);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Call');
    }

    public function forceDelete(AuthUser $authUser, Call $call): bool
    {
        return $authUser->can('ForceDelete:Call') && $this->canAccessCall($authUser, $call);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Call');
    }

    public function recordOutcome(AuthUser $authUser, Call $call): bool
    {
        return $authUser->can('RecordOutcome:Call') && $this->canAccessCall($authUser, $call);
    }

    public function reschedule(AuthUser $authUser, Call $call): bool
    {
        return $authUser->can('Reschedule:Call') && $this->canAccessCall($authUser, $call);
    }

    public function reassign(AuthUser $authUser, Call $call): bool
    {
        return $authUser->can('Reassign:Call') && $this->canAccessCall($authUser, $call);
    }

    public function reassignAny(AuthUser $authUser): bool
    {
        return $authUser->can('Reassign:Call');
    }

    private function canAccessCall(AuthUser $authUser, Call $call): bool
    {
        if (! $authUser instanceof User) {
            return false;
        }

        return Call::query()->visibleTo($authUser)->whereKey($call->getKey())->exists();
    }
}
