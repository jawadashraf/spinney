<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceUsers\Pages;

use App\Filament\Exports\ServiceUserExporter;
use App\Filament\Resources\ServiceUsers\Concerns\HasServiceUserTabNavigation;
use App\Filament\Resources\ServiceUsers\ServiceUserResource;
use App\Models\ServiceUser;
use App\Support\ReportPermissions;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Size;

final class ListServiceUsers extends ListRecords
{
    use HasServiceUserTabNavigation;

    protected static string $resource = ServiceUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->exporter(ServiceUserExporter::class)
                ->color('gray')
                ->size(Size::Small)
                ->visible(fn (): bool => ReportPermissions::canExport(auth()->user(), ServiceUser::class)),
            CreateAction::make(),
        ];
    }
}
