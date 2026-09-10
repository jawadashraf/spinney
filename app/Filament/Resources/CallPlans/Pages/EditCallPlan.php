<?php

declare(strict_types=1);

namespace App\Filament\Resources\CallPlans\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\CallPlans\CallPlanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditCallPlan extends EditRecord
{
    use SyncsPermissionTeamId;

    protected static string $resource = CallPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
