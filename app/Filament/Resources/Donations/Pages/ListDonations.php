<?php

declare(strict_types=1);

namespace App\Filament\Resources\Donations\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Exports\DonationExporter;
use App\Filament\Resources\Donations\DonationResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Size;

final class ListDonations extends ListRecords
{
    use SyncsPermissionTeamId;

    protected static string $resource = DonationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->exporter(DonationExporter::class)
                ->color('gray')
                ->size(Size::Small),
            CreateAction::make()
                ->label('Record donation')
                ->icon('heroicon-o-plus')
                ->size(Size::Small),
        ];
    }
}
