<?php

declare(strict_types=1);

namespace App\Filament\Resources\ThirdPartyCarePlans\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\ThirdPartyCarePlans\ThirdPartyCarePlanResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateThirdPartyCarePlan extends CreateRecord
{
    use SyncsPermissionTeamId;

    protected static string $resource = ThirdPartyCarePlanResource::class;
}
