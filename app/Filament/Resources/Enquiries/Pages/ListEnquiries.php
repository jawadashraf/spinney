<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enquiries\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Exports\EnquiryExporter;
use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Models\Enquiry;
use App\Support\ReportPermissions;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Size;

final class ListEnquiries extends ListRecords
{
    use SyncsPermissionTeamId;

    protected static string $resource = EnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->exporter(EnquiryExporter::class)
                ->color('gray')
                ->size(Size::Small)
                ->visible(fn (): bool => ReportPermissions::canExport(auth()->user(), Enquiry::class)),
            CreateAction::make()
                ->modalWidth('4xl')
                ->slideOver(),
        ];
    }
}
