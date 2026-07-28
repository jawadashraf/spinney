<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use Filament\Facades\Filament;

/**
 * Ensures Spatie Permission's team context is set before authorization
 * checks and model relationship updates run.
 *
 * The tenant middleware (SyncSpatiePermissionsTeamId) only runs on initial
 * page loads, not on Livewire POST requests to /livewire/update. This trait
 * bridges that gap by syncing the team ID from Filament's tenant context,
 * which IS available inside the hydrated Livewire component.
 */
trait SyncsPermissionTeamId
{
    protected function authorizeAccess(): void
    {
        $this->syncSpatieTeamId();

        parent::authorizeAccess();
    }

    protected function beforeFill(): void
    {
        $this->syncSpatieTeamId();
    }

    protected function beforeSave(): void
    {
        $this->syncSpatieTeamId();
    }

    protected function beforeCreate(): void
    {
        $this->syncSpatieTeamId();
    }

    protected function syncSpatieTeamId(): void
    {
        $tenant = Filament::getTenant() ?? auth()->user()?->currentTeam ?? auth()->user()?->allTeams()->first();

        if ($tenant) {
            setPermissionsTeamId($tenant->getKey());
        }
    }
}
