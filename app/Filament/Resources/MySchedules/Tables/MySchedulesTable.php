<?php

declare(strict_types=1);

namespace App\Filament\Resources\MySchedules\Tables;

use App\Models\Schedule;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Zap\Enums\ScheduleTypes;

final class MySchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('schedule_type')
                    ->badge()
                    ->color(fn (string|ScheduleTypes|null $state): string => match ($state instanceof ScheduleTypes ? $state->value : (string) $state) {
                        'availability' => 'success',
                        'blocked' => 'danger',
                        default => 'gray',
                    }),

                IconColumn::make('is_recurring')
                    ->label('Recurring')
                    ->boolean(),

                TextColumn::make('shift_dates')
                    ->label('Dates')
                    ->state(function (Schedule $record): string {
                        $start = $record->start_date->format('M j, Y');
                        $end = $record->end_date ? ' - '.$record->end_date->format('M j, Y') : '';

                        return $start.$end;
                    }),

                TextColumn::make('days')
                    ->label('Days')
                    ->state(function (Schedule $record): string {
                        if (! $record->is_recurring) {
                            return 'N/A';
                        }
                        $config = is_array($record->frequency_config) ? $record->frequency_config : (method_exists($record->frequency_config, 'toArray') ? $record->frequency_config->toArray() : []);
                        /** @var array<int, string> $days */
                        $days = is_array($config['days'] ?? null) ? $config['days'] : [];

                        return collect($days)->map(fn (string $day) => ucfirst(substr($day, 0, 3)))->join(', ');
                    }),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('approval_status')
                    ->label('Approval Status')
                    ->badge()
                    ->state(fn (Schedule $record): string => ($record->metadata['is_approved'] ?? false) ? 'Approved' : 'Pending Approval')
                    ->color(fn (string $state): string => $state === 'Approved' ? 'success' : 'warning'),

                TextColumn::make('periods_summary')
                    ->label('Shift Timings')
                    ->state(fn (Schedule $record): string => $record->periods
                        ->map(fn ($period): string => "{$period->start_time} - {$period->end_time}")
                        ->join(', ')
                    ),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(fn (Schedule $record): string => ($record->metadata['is_approved'] ?? false) ? 'Unapprove' : 'Approve')
                    ->icon(fn (Schedule $record): string => ($record->metadata['is_approved'] ?? false) ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (Schedule $record): string => ($record->metadata['is_approved'] ?? false) ? 'danger' : 'success')
                    ->visible(fn (): bool => auth()->user()?->hasAnyRole(['admin', 'manager']) ?? false)
                    ->action(function (Schedule $record): void {
                        $meta = $record->metadata ?? [];
                        $meta['is_approved'] = ! ($meta['is_approved'] ?? false);
                        $record->update(['metadata' => $meta]);
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
