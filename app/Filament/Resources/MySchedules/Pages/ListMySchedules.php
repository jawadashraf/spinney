<?php

declare(strict_types=1);

namespace App\Filament\Resources\MySchedules\Pages;

use App\Filament\Resources\MySchedules\MyScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListMySchedules extends ListRecords
{
    protected static string $resource = MyScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
