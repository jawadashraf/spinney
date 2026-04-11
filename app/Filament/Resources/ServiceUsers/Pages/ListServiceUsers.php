<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceUsers\Pages;

use App\Filament\Resources\ServiceUsers\ServiceUserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListServiceUsers extends ListRecords
{
    protected static string $resource = ServiceUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
