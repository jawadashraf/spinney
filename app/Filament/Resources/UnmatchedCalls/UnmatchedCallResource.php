<?php

declare(strict_types=1);

namespace App\Filament\Resources\UnmatchedCalls;

use App\Filament\Resources\UnmatchedCalls\Pages\ListUnmatchedCalls;
use App\Filament\Resources\UnmatchedCalls\Tables\UnmatchedCallsTable;
use App\Models\PbxCallLog;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Calls the phone system journalled for numbers nobody in Spinney recognised, waiting to be linked to a
 * person or company, turned into an enquiry, or dismissed. Unlinked calls are pruned after the retention period.
 */
final class UnmatchedCallResource extends Resource
{
    protected static ?string $model = PbxCallLog::class;

    protected static ?string $slug = 'unmatched-calls';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoneXMark;

    protected static string|\UnitEnum|null $navigationGroup = 'Liaison';

    protected static ?string $navigationLabel = 'Unmatched calls';

    protected static ?string $modelLabel = 'unmatched call';

    protected static ?int $navigationSort = 5;

    public static function table(Table $table): Table
    {
        return UnmatchedCallsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUnmatchedCalls::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('ViewAny:Call') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = self::getEloquentQuery()->count();

        return $count > 0 ? (string) $count : null;
    }

    /**
     * @return Builder<PbxCallLog>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<PbxCallLog> $query */
        $query = parent::getEloquentQuery();

        return $query
            ->unmatched()
            ->where('pbx_call_logs.team_id', Filament::getTenant()?->getKey())
            ->visibleTo(auth()->user());
    }
}
