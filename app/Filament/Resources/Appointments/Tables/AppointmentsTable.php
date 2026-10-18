<?php

declare(strict_types=1);

namespace App\Filament\Resources\Appointments\Tables;

use App\Enums\AppointmentStatus;
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
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('schedulable.name')
                    ->label('Counsellor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('attendee')
                    ->label('Attendee')
                    ->state(fn (Schedule $record): string => $record->attendeeName())
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('serviceUser', fn ($q) => $q->where('name', 'like', "%{$search}%"))->orWhere('metadata->external_attendee_name', 'like', "%{$search}%")),
                TextColumn::make('metadata.attendee_type')
                    ->label('Attendee Type')
                    ->formatStateUsing(fn (string $state): string => AttendeeType::tryFrom($state)?->getLabel() ?? '—')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
                TextColumn::make('start_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('selected_slot')
                    ->label('Time Slot')
                    ->state(fn (Schedule $record): string => $record->timeRange()),
                TextColumn::make('appointment_type')
                    ->label('Type')
                    ->state(fn (Schedule $record): ?string => $record->getCounselorType()?->getLabel())
                    ->placeholder('—'),
                TextColumn::make('appointment_status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Schedule $record): string => $record->getAppointmentStatus()->getLabel())
                    ->color(fn (Schedule $record): string => $record->getAppointmentStatus()->getColor()),
                TextColumn::make('attendance')
                    ->badge()
                    ->state(fn (Schedule $record): ?string => $record->getAttendance()?->getLabel())
                    ->color(fn (Schedule $record): string => $record->getAttendance()?->getColor() ?? 'gray')
                    ->placeholder('—'),
                TextColumn::make('metadata.session_type')
                    ->label('Session')
                    ->formatStateUsing(fn (string $state): string => SessionType::tryFrom($state)?->getLabel() ?? '—')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
                TextColumn::make('metadata.payment_type')
                    ->label('Payment')
                    ->formatStateUsing(fn (string $state): string => PaymentType::tryFrom($state)?->getLabel() ?? '—')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable()
                    ->label('Active')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([
                SelectFilter::make('appointment_status')
                    ->label('Status')
                    ->options(AppointmentStatus::class)
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'])
                        ? Schedule::whereAppointmentStatus($query, [AppointmentStatus::from($data['value'])])
                        : $query),
                SelectFilter::make('metadata_counselor_type')
                    ->options(CounselorType::class)
                    ->label('Counselor Specialty')
                    ->query(fn (Builder $query, array $data): Builder => $data['value']
                        ? $query->where('metadata->counselor_type', $data['value'])
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
                    ->query(fn (Builder $query): Builder => $query->where(fn (Builder $query): Builder => $query
                        ->where('end_date', '<', now()->toDateString())
                        ->orWhereNull('end_date')))
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
