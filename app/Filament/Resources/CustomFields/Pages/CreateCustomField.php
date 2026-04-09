<?php

declare(strict_types=1);

namespace App\Filament\Resources\CustomFields\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\CustomFields\CustomFieldResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCustomField extends CreateRecord
{
    use SyncsPermissionTeamId;

    protected static string $resource = CustomFieldResource::class;
}
