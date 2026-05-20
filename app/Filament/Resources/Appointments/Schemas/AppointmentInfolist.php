<?php

declare(strict_types=1);

namespace App\Filament\Resources\Appointments\Schemas;

use App\Enums\AttendeeType;
use App\Enums\CounselorType;
use App\Enums\PaymentType;
use App\Enums\SessionType;
use App\Models\Schedule;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class AppointmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Appointment Details')
                    ->schema([
                        TextEntry::make('name')
                            ->placeholder('—'),
                        TextEntry::make('schedulable.name')
                            ->label('Assigned Counselor'),
                        TextEntry::make('start_date')
                            ->label('Booking Date')
                            ->date(),
                        TextEntry::make('selected_slot')
                            ->label('Time Slot')
                            ->state(fn (Schedule $record): string => isset($record->metadata['start_time']) && isset($record->metadata['end_time'])
                                ? "{$record->metadata['start_time']} - {$record->metadata['end_time']}"
                                : '—'
                            ),
                        TextEntry::make('description')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        IconEntry::make('is_active')
                            ->boolean()
                            ->label('Active'),
                    ])
                    ->columns(2),

                Section::make('Attendee & Billing Info')
                    ->schema([
                        TextEntry::make('metadata.attendee_type')
                            ->label('Attendee Type')
                            ->formatStateUsing(fn (?string $state): string => AttendeeType::tryFrom($state ?? '')?->getLabel() ?? '—')
                            ->badge(),
                        TextEntry::make('serviceUser.name')
                            ->label('Service User')
                            ->visible(fn (Schedule $record): bool => $record->isServiceUserAppointment()),
                        TextEntry::make('metadata.external_attendee_name')
                            ->label('External Attendee Name')
                            ->visible(fn (Schedule $record): bool => ($record->metadata['attendee_type'] ?? null) === AttendeeType::EXTERNAL->value),
                        TextEntry::make('metadata.external_attendee_email')
                            ->label('External Attendee Email')
                            ->visible(fn (Schedule $record): bool => ($record->metadata['attendee_type'] ?? null) === AttendeeType::EXTERNAL->value),
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
                    ->columns(2),

                Section::make('System Metadata')
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
