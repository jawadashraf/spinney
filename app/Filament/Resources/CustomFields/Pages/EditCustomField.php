<?php

declare(strict_types=1);

namespace App\Filament\Resources\CustomFields\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\CustomFields\CustomFieldResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditCustomField extends EditRecord
{
    use SyncsPermissionTeamId;

    protected static string $resource = CustomFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
