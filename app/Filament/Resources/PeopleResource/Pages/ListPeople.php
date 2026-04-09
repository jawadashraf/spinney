<?php

declare(strict_types=1);

namespace App\Filament\Resources\PeopleResource\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Exports\PeopleExporter;
use App\Filament\Imports\PeopleImporter;
use App\Filament\Resources\PeopleResource;
use App\Support\CustomFields\Concerns\InteractsWithCustomFields;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Size;
use Illuminate\Database\Eloquent\Builder;

final class ListPeople extends ListRecords
{
    use InteractsWithCustomFields;
    use SyncsPermissionTeamId;

    protected static string $resource = PeopleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ImportAction::make()->importer(PeopleImporter::class),
                ExportAction::make()->exporter(PeopleExporter::class),
            ])
                ->icon('heroicon-o-arrows-up-down')
                ->color('gray')
                ->button()
                ->label('Import / Export')
                ->size(Size::Small),
            CreateAction::make()->icon('heroicon-o-plus')->size(Size::Small),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All People'),
            'service_users' => Tab::make('Service Users')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'service_user')),
            'donors' => Tab::make('Donors')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'donor')),
            'relatives' => Tab::make('Relatives')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'relative')),
            'professionals' => Tab::make('Professionals')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'professional')),
        ];
    }
}
