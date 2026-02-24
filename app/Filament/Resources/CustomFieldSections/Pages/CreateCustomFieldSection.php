<?php

declare(strict_types=1);

namespace App\Filament\Resources\CustomFieldSections\Pages;

use App\Filament\Resources\CustomFieldSections\CustomFieldSectionResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCustomFieldSection extends CreateRecord
{
    protected static string $resource = CustomFieldSectionResource::class;
}
