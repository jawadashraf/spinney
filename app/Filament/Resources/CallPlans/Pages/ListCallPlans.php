<?php

declare(strict_types=1);

namespace App\Filament\Resources\CallPlans\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\CallPlans\CallPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListCallPlans extends ListRecords
{
    use SyncsPermissionTeamId;

    protected static string $resource = CallPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New call plan'),
        ];
    }
}
