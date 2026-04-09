<?php

declare(strict_types=1);

namespace App\Filament\Resources\ThirdPartyCarePlans\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\ThirdPartyCarePlans\ThirdPartyCarePlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListThirdPartyCarePlans extends ListRecords
{
    use SyncsPermissionTeamId;

    protected static string $resource = ThirdPartyCarePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
