<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Exports\CompanyExporter;
use App\Filament\Imports\CompanyImporter;
use App\Filament\Resources\CompanyResource;
use App\Support\CustomFields\Concerns\InteractsWithCustomFields;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Size;
use Override;

final class ListCompanies extends ListRecords
{
    use InteractsWithCustomFields;
    use SyncsPermissionTeamId;

    /** @var class-string<CompanyResource> */
    protected static string $resource = CompanyResource::class;

    /**
     * Get the actions available on the resource index header.
     */
    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ImportAction::make()
                    ->importer(CompanyImporter::class)
                    ->label('Import companies')
                    ->authorize('create', CompanyResource::getModel()),
                ExportAction::make()
                    ->exporter(CompanyExporter::class)
                    ->label('Export companies'),
            ])
                ->icon('heroicon-o-arrows-up-down')
                ->button()
                ->outlined()
                ->size(Size::Small)
                ->label('Import / Export'),
            CreateAction::make()->icon('heroicon-o-plus')->size(Size::Small),
        ];
    }
}
