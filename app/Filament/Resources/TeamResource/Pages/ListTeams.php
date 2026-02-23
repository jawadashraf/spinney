<?php

declare(strict_types=1);

namespace App\Filament\Resources\TeamResource\Pages;

use App\Filament\Resources\TeamResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListTeams extends ListRecords
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
