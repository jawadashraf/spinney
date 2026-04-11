<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceUsers;

use App\Filament\Resources\ServiceUsers\Pages\CreateServiceUser;
use App\Filament\Resources\ServiceUsers\Pages\EditServiceUser;
use App\Filament\Resources\ServiceUsers\Pages\ListServiceUsers;
use App\Filament\Resources\ServiceUsers\Schemas\ServiceUserForm;
use App\Filament\Resources\ServiceUsers\Tables\ServiceUsersTable;
use App\Models\ServiceUser;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class ServiceUserResource extends Resource
{
    protected static ?string $model = ServiceUser::class;

    protected static ?string $modelLabel = 'Service User';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'Service Users';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return ServiceUserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServiceUsersTable::configure($table);
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
            'index' => ListServiceUsers::route('/'),
            'create' => CreateServiceUser::route('/create'),
            'edit' => EditServiceUser::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
