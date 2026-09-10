<?php

declare(strict_types=1);

namespace App\Filament\Resources\CallPlans;

use App\Filament\Resources\CallPlans\Pages\CreateCallPlan;
use App\Filament\Resources\CallPlans\Pages\EditCallPlan;
use App\Filament\Resources\CallPlans\Pages\ListCallPlans;
use App\Filament\Resources\CallPlans\Schemas\CallPlanForm;
use App\Filament\Resources\CallPlans\Tables\CallPlansTable;
use App\Models\CallPlan;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class CallPlanResource extends Resource
{
    protected static ?string $model = CallPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Liaison';

    protected static ?string $navigationLabel = 'Call plans';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return CallPlanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CallPlansTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCallPlans::route('/'),
            'create' => CreateCallPlan::route('/create'),
            'edit' => EditCallPlan::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<CallPlan>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<CallPlan> $query */
        $query = parent::getEloquentQuery();

        return self::scopeToUser($query);
    }

    /**
     * @return Builder<CallPlan>
     */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        /** @var Builder<CallPlan> $query */
        $query = parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        return self::scopeToUser($query);
    }

    /**
     * @param  Builder<CallPlan>  $query
     * @return Builder<CallPlan>
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
