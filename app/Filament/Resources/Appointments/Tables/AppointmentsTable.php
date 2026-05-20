<?php

declare(strict_types=1);

namespace App\Filament\Resources\Appointments\Tables;

use App\Enums\AttendeeType;
use App\Enums\CounselorType;
use App\Enums\PaymentType;
use App\Enums\SessionType;
use App\Models\Schedule;
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

final class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->limit(30),
                TextColumn::make('schedulable.name')
                    ->label('Assigned Counselor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('attendee')
                    ->label('Attendee')
                    ->state(fn (Schedule $record): string => $record->isServiceUserAppointment()
                        ? ($record->serviceUser?->name ?? '—')
                        : ($record->getExternalAttendeeName() ?? '—')
                    )
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('serviceUser', fn ($q) => $q->where('name', 'like', "%{$search}%"))->orWhere('metadata->external_attendee_name', 'like', "%{$search}%")),
                TextColumn::make('metadata.attendee_type')
                    ->label('Attendee Type')
                    ->formatStateUsing(fn (string $state): string => AttendeeType::tryFrom($state)?->getLabel() ?? '—')
                    ->badge()
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('start_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('selected_slot')
                    ->label('Time Slot')
                    ->state(fn (Schedule $record): string => isset($record->metadata['start_time']) && isset($record->metadata['end_time'])
                        ? "{$record->metadata['start_time']} - {$record->metadata['end_time']}"
                        : '—'
                    ),
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
                SelectFilter::make('metadata_counselor_type')
                    ->options(CounselorType::class)
                    ->label('Counselor Specialty')
                    ->query(fn (Builder $query, array $data): Builder => $data['value']
                        ? $query->whereJsonContains('metadata->counselor_type', $data['value'])
                        : $query),
                SelectFilter::make('metadata_attendee_type')
                    ->options(AttendeeType::class)
                    ->label('Attendee Type')
                    ->query(fn (Builder $query, array $data): Builder => $data['value']
                        ? $query->whereJsonContains('metadata->attendee_type', $data['value'])
                        : $query),
                SelectFilter::make('metadata_session_type')
                    ->options(SessionType::class)
                    ->label('Session Type')
                    ->query(fn (Builder $query, array $data): Builder => $data['value']
                        ? $query->whereJsonContains('metadata->session_type', $data['value'])
                        : $query),
                TernaryFilter::make('is_active')
                    ->label('Active'),
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
