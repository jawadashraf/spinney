<?php

declare(strict_types=1);

namespace App\Filament\Resources\Calls;

use AlizHarb\ActivityLog\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\Calls\Pages\CreateCall;
use App\Filament\Resources\Calls\Pages\EditCall;
use App\Filament\Resources\Calls\Pages\ListCalls;
use App\Filament\Resources\Calls\Pages\ViewCall;
use App\Filament\Resources\Calls\Schemas\CallForm;
use App\Filament\Resources\Calls\Schemas\CallInfolist;
use App\Filament\Resources\Calls\Tables\CallsTable;
use App\Models\Call;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class CallResource extends Resource
{
    protected static ?string $model = Call::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoneArrowUpRight;

    protected static string|\UnitEnum|null $navigationGroup = 'Liaison';

    protected static ?string $navigationLabel = 'Calls';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return CallForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CallInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CallsTable::configure($table);
    }

    public static function getRecordTitle(?Model $record): ?string
    {
        if (! $record instanceof Call) {
            return null;
        }

        return ($record->serviceUser->name ?? 'Call').' – '.$record->due_at->format('d M Y');
    }

    public static function getRelations(): array
    {
        return [
            ActivitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCalls::route('/'),
            'create' => CreateCall::route('/create'),
            'view' => ViewCall::route('/{record}'),
            'edit' => EditCall::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<Call>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<Call> $query */
        $query = parent::getEloquentQuery();

        return self::scopeToUser($query);
    }

    /**
     * @return Builder<Call>
     */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        /** @var Builder<Call> $query */
        $query = parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        return self::scopeToUser($query);
    }

    public static function isManager(): bool
    {
        $user = auth()->user();

        return $user instanceof User && Call::userSeesAllCalls($user);
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public static function scopeAssignableUsers(Builder $query): Builder
    {
        return $query->whereHas('roles', fn (Builder $roles): Builder => $roles->whereIn('name', Call::ASSIGNABLE_ROLES));
    }

    /**
     * @return array<int, string>
     */
    public static function assignableUserOptions(): array
    {
        return self::scopeAssignableUsers(User::query())
            ->get()
            ->sortBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @param  Builder<Call>  $query
     * @return Builder<Call>
     */
    private static function scopeToUser(Builder $query): Builder
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return $query->whereRaw('0 = 1');
        }

        return $query->visibleTo($user);
    }
}
