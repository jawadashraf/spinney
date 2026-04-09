<?php

declare(strict_types=1);

namespace App\Filament\Resources\Schedules\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\Schedules\ScheduleResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateSchedule extends CreateRecord
{
    use SyncsPermissionTeamId;

    protected static string $resource = ScheduleResource::class;
}
