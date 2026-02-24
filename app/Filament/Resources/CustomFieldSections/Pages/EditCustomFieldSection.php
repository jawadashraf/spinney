<?php

declare(strict_types=1);

namespace App\Filament\Resources\CustomFieldSections\Pages;

use App\Filament\Resources\CustomFieldSections\CustomFieldSectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditCustomFieldSection extends EditRecord
{
    protected static string $resource = CustomFieldSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
