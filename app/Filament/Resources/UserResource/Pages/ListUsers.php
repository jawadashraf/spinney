<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

final class ListUsers extends ListRecords
{
    use SyncsPermissionTeamId;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $tenant = Filament::getTenant();
                    $teamId = $tenant ? $tenant->getKey() : auth()->user()?->current_team_id;
                    if ($teamId) {
                        $data['current_team_id'] = $teamId;
                    }

                    return $data;
                })
                ->after(function (User $record): void {
                    $tenant = Filament::getTenant();
                    $teamId = $tenant ? $tenant->getKey() : auth()->user()?->current_team_id;
                    if ($teamId && ! $record->teams()->where('team_id', $teamId)->exists()) {
                        $record->teams()->attach($teamId);
                    }
                }),
        ];
    }
}
