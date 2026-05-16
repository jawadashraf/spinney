<?php

declare(strict_types=1);

namespace App\Filament\Resources\Schedules\Tables;

use App\Enums\AttendeeType;
use App\Enums\CounselorType;
use App\Enums\PaymentType;
use App\Enums\ScheduleType;
use App\Enums\SessionType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Zap\Enums\ScheduleTypes;

final class SchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('schedule_type')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        ScheduleTypes::AVAILABILITY => 'success',
                        ScheduleTypes::APPOINTMENT => 'info',
                        ScheduleTypes::BLOCKED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (ScheduleTypes $state): string => $state->name)
                    ->sortable()
                    ->label('Type'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->limit(30),
                TextColumn::make('schedulable.name')
                    ->label('Assigned To')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('metadata.counselor_type')
                    ->label('Specialty')
                    ->formatStateUsing(fn (string $state): string => CounselorType::tryFrom($state)?->getLabel() ?? '—')
                    ->badge()
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->placeholder('No end')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('metadata.slot_duration_minutes')
                    ->label('Slot')
                    ->formatStateUsing(fn (string $state): string => $state.' min')
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('metadata.capacity')
                    ->label('Capacity')
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('metadata.attendee_type')
                    ->label('Attendee')
                    ->formatStateUsing(fn (string $state): string => AttendeeType::tryFrom($state)?->getLabel() ?? '—')
                    ->badge()
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('metadata.session_type')
                    ->label('Session')
                    ->formatStateUsing(fn (string $state): string => SessionType::tryFrom($state)?->getLabel() ?? '—')
                    ->badge()
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('metadata.payment_type')
                    ->label('Payment')
                    ->formatStateUsing(fn (string $state): string => PaymentType::tryFrom($state)?->getLabel() ?? '—')
                    ->badge()
                    ->toggleable()
                    ->placeholder('—'),
                IconColumn::make('is_recurring')
                    ->boolean()
                    ->sortable()
                    ->label('Recurring'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable()
                    ->label('Active'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([
                SelectFilter::make('schedule_type')
                    ->options(ScheduleType::class)
                    ->label('Type'),
                SelectFilter::make('metadata_counselor_type')
                    ->options(CounselorType::class)
                    ->label('Specialty')
                    ->query(fn (Builder $query, array $data): Builder => $data['value']
                        ? $query->whereJsonContains('metadata->counselor_type', $data['value'])
                        : $query),
                TernaryFilter::make('is_active')
                    ->label('Active'),
                TernaryFilter::make('is_recurring')
                    ->label('Recurring'),
                Filter::make('upcoming')
                    ->query(fn (Builder $query): Builder => $query->where('start_date', '>=', now()->toDateString()))
                    ->label('Upcoming'),
                Filter::make('past')
                    ->query(fn (Builder $query): Builder => $query->where('end_date', '<', now()->toDateString())->orWhereNull('end_date'))
                    ->label('Past'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
