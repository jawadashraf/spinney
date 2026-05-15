<?php

declare(strict_types=1);

namespace App\Filament\Resources\Schedules\Schemas;

use App\Enums\AttendeeType;
use App\Enums\CounselorType;
use App\Enums\PaymentType;
use App\Enums\ScheduleFrequency;
use App\Enums\ScheduleType;
use App\Enums\SessionType;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ScheduleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Schedule Details')
                    ->schema([
                        TextEntry::make('schedule_type')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                ScheduleType::AVAILABILITY->value => 'success',
                                ScheduleType::APPOINTMENT->value => 'info',
                                ScheduleType::BLOCKED->value => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => ScheduleType::from($state)->getLabel()),
                        TextEntry::make('schedulable.name')
                            ->label('Assigned To'),
                        TextEntry::make('name')
                            ->placeholder('—'),
                        TextEntry::make('description')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        IconEntry::make('is_active')
                            ->boolean()
                            ->label('Active'),
                    ])
                    ->columns(2),

                Section::make('Scheduling')
                    ->schema([
                        TextEntry::make('start_date')
                            ->date(),
                        TextEntry::make('end_date')
                            ->date()
                            ->placeholder('No end date'),
                        IconEntry::make('is_recurring')
                            ->boolean()
                            ->label('Recurring'),
                        TextEntry::make('frequency')
                            ->formatStateUsing(fn (?string $state): string => $state ? ScheduleFrequency::tryFrom($state)?->getLabel() ?? $state : '—')
                            ->visible(fn ($record): bool => $record->is_recurring)
                            ->placeholder('—'),
                    ])
                    ->columns(2),

                Section::make('Availability Info')
                    ->schema([
                        TextEntry::make('metadata.counselor_type')
                            ->label('Counselor Specialty')
                            ->formatStateUsing(fn (?string $state): string => CounselorType::tryFrom($state ?? '')?->getLabel() ?? '—')
                            ->badge(),
                        TextEntry::make('metadata.slot_duration_minutes')
                            ->label('Slot Duration')
                            ->formatStateUsing(fn (?string $state): string => $state ? $state.' minutes' : '—'),
                        TextEntry::make('metadata.capacity')
                            ->label('Capacity'),
                        IconEntry::make('metadata.is_locked')
                            ->boolean()
                            ->label('Locked'),
                    ])
                    ->columns(2)
                    ->visible(fn ($record): bool => ($record->schedule_type->value ?? '') === ScheduleType::AVAILABILITY->value),

                Section::make('Appointment Info')
                    ->schema([
                        TextEntry::make('metadata.attendee_type')
                            ->label('Attendee Type')
                            ->formatStateUsing(fn (?string $state): string => AttendeeType::tryFrom($state ?? '')?->getLabel() ?? '—')
                            ->badge(),
                        TextEntry::make('serviceUser.name')
                            ->label('Service User')
                            ->visible(fn ($record): bool => $record->isServiceUserAppointment()),
                        TextEntry::make('metadata.external_attendee_name')
                            ->label('External Attendee')
                            ->visible(fn ($record): bool => ($record->metadata['attendee_type'] ?? null) === AttendeeType::EXTERNAL->value),
                        TextEntry::make('metadata.external_attendee_email')
                            ->label('External Email')
                            ->visible(fn ($record): bool => ($record->metadata['attendee_type'] ?? null) === AttendeeType::EXTERNAL->value),
                        TextEntry::make('metadata.counselor_type')
                            ->label('Appointment Type')
                            ->formatStateUsing(fn (?string $state): string => CounselorType::tryFrom($state ?? '')?->getLabel() ?? '—')
                            ->badge(),
                        TextEntry::make('metadata.session_type')
                            ->label('Session Type')
                            ->formatStateUsing(fn (?string $state): string => SessionType::tryFrom($state ?? '')?->getLabel() ?? '—')
                            ->badge(),
                        TextEntry::make('metadata.payment_type')
                            ->label('Payment')
                            ->formatStateUsing(fn (?string $state): string => PaymentType::tryFrom($state ?? '')?->getLabel() ?? '—')
                            ->badge(),
                        TextEntry::make('metadata.care_plan_id')
                            ->label('Care Plan ID')
                            ->placeholder('—'),
                    ])
                    ->columns(2)
                    ->visible(fn ($record): bool => ($record->schedule_type->value ?? '') === ScheduleType::APPOINTMENT->value),

                Section::make('Blocked Time')
                    ->schema([
                        TextEntry::make('metadata.reason')
                            ->label('Reason')
                            ->placeholder('No reason provided')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record): bool => ($record->schedule_type->value ?? '') === ScheduleType::BLOCKED->value),

                Section::make('Time Slots')
                    ->schema([
                        TextEntry::make('periods')
                            ->label('Total Periods')
                            ->state(fn ($record): int => $record->periods()->count()),
                        TextEntry::make('total_duration')
                            ->label('Total Duration')
                            ->state(fn ($record): string => $record->total_duration.' minutes'),
                    ])
                    ->columns(2),

                Section::make('Metadata')
                    ->schema([
                        TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Created'),
                        TextEntry::make('updated_at')
                            ->dateTime()
                            ->label('Updated'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
