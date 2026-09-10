<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class TeamManagers
{
    /**
     * Admins and managers of the given team.
     *
     * @return Collection<int, User>
     */
    public static function for(int $teamId): Collection
    {
        setPermissionsTeamId($teamId);

        return User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'manager']))
            ->get();
    }
}
