<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceUsers;


use App\Filament\Resources\ServiceUsers\Pages\CreateServiceUser;
use App\Filament\Resources\ServiceUsers\Pages\EditServiceUser;
use App\Filament\Resources\ServiceUsers\Pages\ListServiceUsers;
use App\Filament\Resources\ServiceUsers\RelationManagers\NotesRelationManager;
use App\Filament\Resources\ServiceUsers\RelationManagers\ServiceUserAppointmentsRelationManager;
use App\Filament\Resources\ServiceUsers\RelationManagers\ThirdPartyCarePlansRelationManager;
use App\Filament\Resources\ServiceUsers\Schemas\ServiceUserForm;
use App\Filament\Resources\ServiceUsers\Tables\ServiceUsersTable;
use App\Models\ServiceUser;
use App\Models\User;
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
            NotesRelationManager::class,
            ThirdPartyCarePlansRelationManager::class,
            ServiceUserAppointmentsRelationManager::class,
            RelationManagers\ServiceUserActivitiesRelationManager::class,
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

    /**
     * @return Builder<ServiceUser>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<ServiceUser> $query */
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        return self::applyVolunteerLiaisonScope($query);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        /** @var Builder<ServiceUser> $query */
        $query = parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        return self::applyVolunteerLiaisonScope($query);
    }

    /**
     * @param  Builder<ServiceUser>  $query
     * @return Builder<ServiceUser>
     */
    protected static function applyVolunteerLiaisonScope(Builder $query): Builder
    {
        /** @var User|null $user */
        $user = auth()->user();

        if ($user && $user->isRestrictedVolunteerLiaison()) {
            $query->visibleToVolunteerLiaison($user);
        }

        return $query;
    }
}
