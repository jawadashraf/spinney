<?php

declare(strict_types=1);

namespace App\Filament\Resources\Calls\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\Calls\Actions\CoverAbsenceAction;
use App\Filament\Resources\Calls\CallResource;
use App\Models\Call;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

final class ListCalls extends ListRecords
{
    use SyncsPermissionTeamId;

    protected static string $resource = CallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CoverAbsenceAction::make(),
            CreateAction::make()
                ->label('Schedule call'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'mine' => Tab::make('My calls')
                ->icon('heroicon-o-user')
                ->modifyQueryUsing(fn (Builder $query): Builder => self::mine($query))
                ->badge(fn (): int => self::mine(CallResource::getEloquentQuery())->count())
                ->deferBadge(),
            'today' => Tab::make('Due today')
                ->modifyQueryUsing(fn (Builder $query): Builder => self::calls($query)->dueToday())
                ->badge(fn (): int => CallResource::getEloquentQuery()->dueToday()->count())
                ->deferBadge(),
            'overdue' => Tab::make('Overdue')
                ->modifyQueryUsing(fn (Builder $query): Builder => self::calls($query)->overdue())
                ->badge(fn (): int => CallResource::getEloquentQuery()->overdue()->count())
                ->badgeColor('danger')
                ->deferBadge(),
            'upcoming' => Tab::make('Upcoming')
                ->modifyQueryUsing(fn (Builder $query): Builder => self::calls($query)->upcoming()),
            'history' => Tab::make('History')
                ->modifyQueryUsing(fn (Builder $query): Builder => self::calls($query)->history()->reorder('calls.due_at', 'desc')),
            'all' => Tab::make('All'),
        ];
    }

    public function getDefaultActiveTab(): string
    {
        return CallResource::isManager() ? 'overdue' : 'mine';
    }

    /**
     * @param  Builder<Call>  $query
     * @return Builder<Call>
     */
    private static function mine(Builder $query): Builder
    {
        return $query->open()->where('calls.assigned_user_id', auth()->id());
    }

    /**
     * Narrow the tab query to the Call builder so its local scopes are typed.
     *
     * @param  Builder<Call>  $query
     * @return Builder<Call>
     */
    private static function calls(Builder $query): Builder
    {
        return $query;
    }
}
