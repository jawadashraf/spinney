<?php

declare(strict_types=1);

namespace App\Filament\Resources\Calls\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\Calls\CallResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditCall extends EditRecord
{
    use SyncsPermissionTeamId;

    protected static string $resource = CallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
