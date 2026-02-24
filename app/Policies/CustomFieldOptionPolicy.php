<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CustomFieldOption;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class CustomFieldOptionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    public function view(): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    public function update(User $user, CustomFieldOption $customFieldOption): bool
    {
        return $user->belongsToTeam($customFieldOption->team);
    }

    public function delete(User $user, CustomFieldOption $customFieldOption): bool
    {
        return $user->belongsToTeam($customFieldOption->team);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    public function restore(User $user, CustomFieldOption $customFieldOption): bool
    {
        return $user->belongsToTeam($customFieldOption->team);
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    public function forceDelete(User $user): bool
    {
        return $user->currentTeam && $user->hasTeamRole($user->currentTeam, 'admin');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->currentTeam && $user->hasTeamRole($user->currentTeam, 'admin');
    }
}
