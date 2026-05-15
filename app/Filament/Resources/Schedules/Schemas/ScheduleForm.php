<?php

declare(strict_types=1);

namespace App\Filament\Resources\Schedules\Schemas;

use App\Enums\AttendeeType;
use App\Enums\CounselorType;
use App\Enums\PaymentType;
use App\Enums\ScheduleFrequency;
use App\Enums\ScheduleType;
use App\Enums\SessionType;
use App\Models\People;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

final class ScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Schedule Type')
                    ->schema([
                        Select::make('schedule_type')
                            ->options(ScheduleType::class)
                            ->required()
                            ->default(ScheduleType::APPOINTMENT->value)
                            ->live()
                            ->native(false),
                    ]),

                Section::make('Assigned To')
                    ->schema([
                        MorphToSelect::make('schedulable')
                            ->types([
                                MorphToSelect\Type::make(User::class)
                                    ->titleAttribute('name')
                                    ->modifyOptionsQueryUsing(fn ($query) => $query->whereHas('roles', fn ($q) => $q->whereNot('name', 'service_user')))
                                    ->label('Counselor / Staff'),
                                MorphToSelect\Type::make(People::class)
                                    ->titleAttribute('name')
                                    ->label('Person'),
                            ])
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->visible(fn (Get $get): bool => filled($get('schedule_type'))),

                Section::make('Details')
                    ->schema([
                        TextInput::make('name')
                            ->maxLength(255)
                            ->placeholder('e.g. Office Hours - Drug Counseling')
                            ->required(fn (Get $get): bool => ($get('schedule_type') ?? '') !== ScheduleType::BLOCKED->value),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Optional details about this schedule'),
                        Toggle::make('is_active')
                            ->default(true)
                            ->label('Active'),
                    ])
                    ->columns(2),

                Section::make('Date & Recurrence')
                    ->schema([
                        DatePicker::make('start_date')
                            ->required()
                            ->native(false)
                            ->minDate(fn (string $context): ?string => $context === 'create' ? now()->toDateString() : null)
                            ->live(),
                        DatePicker::make('end_date')
                            ->native(false)
                            ->minDate(fn (Get $get): ?string => $get('start_date')),
                        Toggle::make('is_recurring')
                            ->default(false)
                            ->live()
                            ->label('Recurring Schedule'),
                        Select::make('frequency')
                            ->options(ScheduleFrequency::class)
                            ->visible(fn (Get $get): bool => $get('is_recurring') === true)
                            ->required(fn (Get $get): bool => $get('is_recurring') === true)
                            ->native(false)
                            ->live(),
                        KeyValue::make('frequency_config')
                            ->visible(fn (Get $get): bool => $get('is_recurring') === true && filled($get('frequency')))
                            ->keyLabel('Setting')
                            ->valueLabel('Value')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Availability Settings')
                    ->schema([
                        Select::make('metadata.counselor_type')
                            ->options(CounselorType::class)
                            ->native(false)
                            ->label('Counselor Specialty')
                            ->required(),
                        Select::make('metadata.slot_duration_minutes')
                            ->options([
                                15 => '15 minutes',
                                30 => '30 minutes',
                                45 => '45 minutes',
                                60 => '60 minutes',
                            ])
                            ->required()
                            ->default(60)
                            ->native(false)
                            ->label('Slot Duration'),
                        TextInput::make('metadata.capacity')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(50)
                            ->label('Capacity')
                            ->helperText('1 for individual sessions, 2+ for group sessions'),
                        Toggle::make('metadata.is_locked')
                            ->default(false)
                            ->label('Lock Schedule')
                            ->helperText('When locked, counselors cannot edit this availability'),
                    ])
                    ->visible(fn (Get $get): bool => ($get('schedule_type') ?? '') === ScheduleType::AVAILABILITY->value)
                    ->columns(2),

                Section::make('Time Slots')
                    ->description(fn (Get $get): ?string => ($get('schedule_type') ?? '') === ScheduleType::AVAILABILITY->value
                        ? 'Define working hours. Bookable slots are generated automatically based on slot duration.'
                        : 'Define the start and end time for this schedule.')
                    ->schema([
                        TimePicker::make('period_start_time')
                            ->label('Start Time')
                            ->required()
                            ->native(false)
                            ->seconds(false)
                            ->default('09:00')
                            ->live(),
                        TimePicker::make('period_end_time')
                            ->label('End Time')
                            ->required()
                            ->native(false)
                            ->seconds(false)
                            ->default('17:00')
                            ->after('period_start_time'),
                    ])
                    ->visible(fn (Get $get): bool => filled($get('schedule_type')))
                    ->columns(2),

                Section::make('Appointment Details')
                    ->schema([
                        Select::make('metadata.attendee_type')
                            ->options(AttendeeType::class)
                            ->live()
                            ->native(false)
                            ->label('Attendee Type')
                            ->required(),
                        Select::make('metadata.service_user_id')
                            ->relationship('serviceUser', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => ($get('metadata.attendee_type') ?? '') === AttendeeType::SERVICE_USER->value)
                            ->label('Service User')
                            ->required(fn (Get $get): bool => ($get('metadata.attendee_type') ?? '') === AttendeeType::SERVICE_USER->value),
                        TextInput::make('metadata.external_attendee_name')
                            ->maxLength(255)
                            ->visible(fn (Get $get): bool => ($get('metadata.attendee_type') ?? '') === AttendeeType::EXTERNAL->value)
                            ->label('External Attendee Name')
                            ->required(fn (Get $get): bool => ($get('metadata.attendee_type') ?? '') === AttendeeType::EXTERNAL->value),
                        TextInput::make('metadata.external_attendee_email')
                            ->email()
                            ->maxLength(255)
                            ->visible(fn (Get $get): bool => ($get('metadata.attendee_type') ?? '') === AttendeeType::EXTERNAL->value)
                            ->label('External Attendee Email'),
                        Select::make('metadata.counselor_type')
                            ->options(CounselorType::class)
                            ->native(false)
                            ->label('Appointment Type')
                            ->required(),
                        Select::make('metadata.session_type')
                            ->options(SessionType::class)
                            ->native(false)
                            ->default(SessionType::INDIVIDUAL->value)
                            ->label('Session Type')
                            ->required(),
                        Select::make('metadata.payment_type')
                            ->options(PaymentType::class)
                            ->native(false)
                            ->default(PaymentType::FREE->value)
                            ->label('Payment Type'),
                        TextInput::make('metadata.care_plan_id')
                            ->numeric()
                            ->label('Care Plan ID')
                            ->placeholder('Optional linked care plan'),
                    ])
                    ->visible(fn (Get $get): bool => ($get('schedule_type') ?? '') === ScheduleType::APPOINTMENT->value)
                    ->columns(2),

                Section::make('Blocked Time Details')
                    ->schema([
                        Textarea::make('metadata.reason')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('e.g. Annual Leave, Training, Bank Holiday')
                            ->label('Reason for Blocking'),
                    ])
                    ->visible(fn (Get $get): bool => ($get('schedule_type') ?? '') === ScheduleType::BLOCKED->value),
            ]);
    }
}
