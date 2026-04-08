<?php

declare(strict_types=1);

namespace App\Filament\Resources\ThirdPartyCarePlans\Pages;

use App\Filament\Resources\ThirdPartyCarePlans\ThirdPartyCarePlanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewThirdPartyCarePlan extends ViewRecord
{
    protected static string $resource = ThirdPartyCarePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
