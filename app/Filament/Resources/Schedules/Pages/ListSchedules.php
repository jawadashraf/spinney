<?php

declare(strict_types=1);

namespace App\Filament\Resources\Schedules\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\Schedules\Actions\ViewBookableSlotsAction;
use App\Filament\Resources\Schedules\ScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListSchedules extends ListRecords
{
    use SyncsPermissionTeamId;

    protected static string $resource = ScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewBookableSlotsAction::make('view_bookable_slots'),
            CreateAction::make(),
        ];
    }
}
