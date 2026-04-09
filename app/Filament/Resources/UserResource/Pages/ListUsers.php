<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\ListRecords;

final class ListUsers extends ListRecords
{
    use SyncsPermissionTeamId;

    protected static string $resource = UserResource::class;
}
