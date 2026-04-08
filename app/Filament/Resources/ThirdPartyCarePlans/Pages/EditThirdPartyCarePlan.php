<?php

declare(strict_types=1);

namespace App\Filament\Resources\ThirdPartyCarePlans\Pages;

use App\Filament\Resources\ThirdPartyCarePlans\ThirdPartyCarePlanResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditThirdPartyCarePlan extends EditRecord
{
    protected static string $resource = ThirdPartyCarePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
