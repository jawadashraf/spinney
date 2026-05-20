<?php

declare(strict_types=1);

namespace App\Filament\Resources\Appointments\Schemas;

use App\Enums\AttendeeType;
use App\Enums\CounselorType;
use App\Enums\PaymentType;
use App\Enums\ScheduleType;
use App\Enums\SessionType;
use App\Models\People;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Relations\Relation;
use Zap\Models\Schedule;

final class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make([
                    'default' => 1,
                    'lg' => 3,
                ])
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                Section::make('Counselor & Time Selection')
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
                                            ->required()
                                            ->live(),

                                        DatePicker::make('booking_date')
                                            ->label('Booking Date')
                                            ->required()
                                            ->live()
                                            ->native(false)
                                            ->minDate(now()->toDateString()),

                                        Select::make('selected_slot')
                                            ->label('Available 60-Minute Slots')
                                            ->required()
                                            ->live()
                                            ->options(function (Get $get) {
                                                $counselorId = $get('schedulable_id');
                                                $counselorType = $get('schedulable_type');
                                                $date = $get('booking_date');

                                                if (! $counselorId || ! $date) {
                                                    return [];
                                                }

                                                $schedulableClass = Relation::getMorphedModel($counselorType) ?? $counselorType;
                                                if (! class_exists($schedulableClass)) {
                                                    return [];
                                                }

                                                $counselor = $schedulableClass::find($counselorId);
                                                if (! $counselor) {
                                                    return [];
                                                }

                                                $slots = $counselor->getBookableSlots($date, 60);

                                                return collect($slots)
                                                    ->filter(fn ($slot) => (bool) ($slot['is_available'] ?? false))
                                                    ->mapWithKeys(fn ($slot) => [
                                                        "{$slot['start_time']}-{$slot['end_time']}" => "{$slot['start_time']} - {$slot['end_time']}",
                                                    ])
                                                    ->toArray();
                                            })
                                            ->visible(fn (Get $get): bool => filled($get('schedulable_id')) && filled($get('booking_date'))),
                                    ]),

                                Section::make('General')
                                    ->schema([
                                        TextInput::make('name')
                                            ->maxLength(255)
                                            ->placeholder('e.g. Weekly Therapy Session')
                                            ->required(),

                                        Textarea::make('description')
                                            ->rows(3)
                                            ->columnSpanFull()
                                            ->placeholder('Optional details or notes about this appointment'),

                                        Toggle::make('is_active')
                                            ->default(true)
                                            ->label('Active'),
                                    ]),
                            ])
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 2,
                            ]),

                        Section::make('Attendee & Booking Details')
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
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 1,
                            ]),
                    ]),
            ]);
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        $data['schedule_type'] = ScheduleType::APPOINTMENT->value;

        $bookingDate = $data['booking_date'] ?? null;
        $selectedSlot = $data['selected_slot'] ?? null;
        if ($bookingDate && $selectedSlot) {
            [$startTime, $endTime] = explode('-', $selectedSlot);
            $data['start_date'] = $bookingDate;
            $data['end_date'] = $bookingDate;

            $data['metadata'] = array_merge($data['metadata'] ?? [], [
                'start_time' => $startTime,
                'end_time' => $endTime,
            ]);

            request()->merge([
                'period_start_time' => $startTime,
                'period_end_time' => $endTime,
                'data' => array_merge(request()->input('data', []), [
                    'period_start_time' => $startTime,
                    'period_end_time' => $endTime,
                ]),
            ]);
        }

        $data['is_recurring'] = false;
        $data['frequency'] = null;
        $data['frequency_config'] = null;

        return $data;
    }

    public static function fillFormFromRecord(Schedule $record): array
    {
        $data = [];
        $data['booking_date'] = $record->start_date ? Carbon::parse($record->start_date)->toDateString() : null;
        $meta = $record->metadata ?? [];
        if (isset($meta['start_time']) && isset($meta['end_time'])) {
            $data['selected_slot'] = "{$meta['start_time']}-{$meta['end_time']}";
        }
        $data['is_recurring'] = false;

        return $data;
    }
}
