<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\Calls\Actions\RecordCallOutcomeAction;
use App\Filament\Resources\Calls\CallResource;
use App\Models\Call;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

final class UpcomingCallsWidget extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Upcoming calls';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => CallResource::getEloquentQuery()
                ->upcoming()
                ->with(['serviceUser', 'assignee', 'plan'])
                ->orderBy('due_at'))
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('serviceUser.name')
                    ->label('Service user'),
                TextColumn::make('due_at')
                    ->label('Due')
                    ->dateTime('D d M, H:i'),
                TextColumn::make('assignee.name')
                    ->label('Liaison')
                    ->placeholder('Unassigned')
                    ->badge(),
                TextColumn::make('plan.frequency')
                    ->label('Frequency')
                    ->badge()
                    ->placeholder('Ad-hoc'),
                TextColumn::make('attempt_count')
                    ->label('Attempts')
                    ->alignCenter(),
            ])
            ->recordActions([
                RecordCallOutcomeAction::make(),
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->iconButton()
                    ->url(fn (Call $record): string => CallResource::getUrl('view', ['record' => $record])),
            ]);
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Call::class) ?? false;
    }
}
