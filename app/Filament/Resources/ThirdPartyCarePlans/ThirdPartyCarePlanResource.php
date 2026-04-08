<?php

declare(strict_types=1);

namespace App\Filament\Resources\ThirdPartyCarePlans;

use App\Filament\Resources\ThirdPartyCarePlans\Pages\CreateThirdPartyCarePlan;
use App\Filament\Resources\ThirdPartyCarePlans\Pages\EditThirdPartyCarePlan;
use App\Filament\Resources\ThirdPartyCarePlans\Pages\ListThirdPartyCarePlans;
use App\Filament\Resources\ThirdPartyCarePlans\Pages\ViewThirdPartyCarePlan;
use App\Filament\Resources\ThirdPartyCarePlans\Schemas\ThirdPartyCarePlanForm;
use App\Filament\Resources\ThirdPartyCarePlans\Schemas\ThirdPartyCarePlanInfolist;
use App\Filament\Resources\ThirdPartyCarePlans\Tables\ThirdPartyCarePlansTable;
use App\Models\ThirdPartyCarePlan;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class ThirdPartyCarePlanResource extends Resource
{
    protected static ?string $model = ThirdPartyCarePlan::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 10;

    protected static string|\UnitEnum|null $navigationGroup = 'Service Users';

    protected static ?string $navigationLabel = 'External Care Plans';

    protected static ?string $pluralNavigationLabel = 'External Care Plans';

    protected static ?string $recordTitleAttribute = 'provider_name';

    public static function form(Schema $schema): Schema
    {
        return ThirdPartyCarePlanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ThirdPartyCarePlanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ThirdPartyCarePlansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListThirdPartyCarePlans::route('/'),
            'create' => CreateThirdPartyCarePlan::route('/create'),
            'view' => ViewThirdPartyCarePlan::route('/{record}'),
            'edit' => EditThirdPartyCarePlan::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGlobalSearchAttributes(): array
    {
        return ['provider_name', 'status', 'serviceUser.name'];
    }
}
