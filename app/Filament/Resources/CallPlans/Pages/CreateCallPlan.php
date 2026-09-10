<?php

declare(strict_types=1);

namespace App\Filament\Resources\CallPlans\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\CallPlans\CallPlanResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCallPlan extends CreateRecord
{
    use SyncsPermissionTeamId;

    protected static string $resource = CallPlanResource::class;
}
