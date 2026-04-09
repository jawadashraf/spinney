<?php

declare(strict_types=1);

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\TaskResource;
use App\Support\CustomFields\Concerns\InteractsWithCustomFields;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Size;

final class ManageTasks extends ManageRecords
{
    use InteractsWithCustomFields;
    use SyncsPermissionTeamId;

    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus')->size(Size::Small)->slideOver(),
        ];
    }
}
