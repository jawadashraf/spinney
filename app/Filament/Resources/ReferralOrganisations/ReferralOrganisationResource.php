<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReferralOrganisations;

use App\Filament\Resources\ReferralOrganisations\Pages\CreateReferralOrganisation;
use App\Filament\Resources\ReferralOrganisations\Pages\EditReferralOrganisation;
use App\Filament\Resources\ReferralOrganisations\Pages\ListReferralOrganisations;
use App\Filament\Resources\ReferralOrganisations\Pages\ViewReferralOrganisation;
use App\Filament\Resources\ReferralOrganisations\Schemas\ReferralOrganisationForm;
use App\Filament\Resources\ReferralOrganisations\Schemas\ReferralOrganisationInfolist;
use App\Filament\Resources\ReferralOrganisations\Tables\ReferralOrganisationsTable;
use App\Models\ReferralOrganisation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class ReferralOrganisationResource extends Resource
{
    protected static ?string $model = ReferralOrganisation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|\UnitEnum|null $navigationGroup = 'Liaison';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Referral Directory';

    protected static ?string $modelLabel = 'Referral Organisation';

    protected static ?string $pluralModelLabel = 'Referral Directory';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ReferralOrganisationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ReferralOrganisationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReferralOrganisationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReferralOrganisations::route('/'),
            'create' => CreateReferralOrganisation::route('/create'),
            'view' => ViewReferralOrganisation::route('/{record}'),
            'edit' => EditReferralOrganisation::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * @return array<int, string>
     */
    public static function getGlobalSearchAttributes(): array
    {
        return ['name', 'area_of_support', 'what_they_help_with', 'telephone'];
    }
}
