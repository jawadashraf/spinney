<?php

declare(strict_types=1);

namespace App\Filament\Resources\CustomFieldSections\Pages;

use App\Filament\Resources\CustomFieldSections\CustomFieldSectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListCustomFieldSections extends ListRecords
{
    protected static string $resource = CustomFieldSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
