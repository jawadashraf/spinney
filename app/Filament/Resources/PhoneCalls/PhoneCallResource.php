<?php

declare(strict_types=1);

namespace App\Filament\Resources\PhoneCalls;

use App\Filament\Resources\PhoneCalls\Pages\ListPhoneCalls;
use App\Filament\Resources\PhoneCalls\Tables\PhoneCallsTable;
use App\Models\PbxCallLog;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Every call the phone system journalled, matched or not. Liaisons see the calls they made or answered;
 * managers and admins see them all.
 */
final class PhoneCallResource extends Resource
{
    protected static ?string $model = PbxCallLog::class;

    protected static ?string $slug = 'phone-calls';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static string|\UnitEnum|null $navigationGroup = 'Liaison';

    protected static ?string $navigationLabel = 'Phone calls';

    protected static ?string $modelLabel = 'phone call';

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return PhoneCallsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPhoneCalls::route('/'),
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

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = self::getEloquentQuery()->needingNotes()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): string
    {
        return 'Answered calls that need notes';
    }

    /**
     * @return Builder<PbxCallLog>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<PbxCallLog> $query */
        $query = parent::getEloquentQuery();

        return $query
            ->where('pbx_call_logs.team_id', Filament::getTenant()?->getKey())
            ->visibleTo(auth()->user());
    }
}
