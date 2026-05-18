<?php

declare(strict_types=1);

namespace App\Filament\Resources\Schedules\Schemas;

use App\Enums\AttendeeType;
use App\Enums\CounselorType;
use App\Enums\PaymentType;
use App\Enums\SessionType;
use Carbon\Carbon;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Zap\Enums\ScheduleTypes;

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
                            ->color(fn (ScheduleTypes $state): string => match ($state) {
                                ScheduleTypes::AVAILABILITY => 'success',
                                ScheduleTypes::APPOINTMENT => 'info',
                                ScheduleTypes::BLOCKED => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (ScheduleTypes $state): string => $state->name),
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
                        TextEntry::make('metadata.timezone')
                            ->label('Timezone')
                            ->visible(fn ($record): bool => (bool) $record->is_recurring && ! empty($record->metadata['timezone'] ?? null))
                            ->placeholder('—'),
                        TextEntry::make('recurrence_summary')
                            ->label('Recurrence Pattern')
                            ->visible(fn ($record): bool => (bool) $record->is_recurring)
                            ->state(function ($record): string {
                                $frequency = $record->frequency;
                                $config = $record->frequency_config;

                                if (! $frequency) {
                                    return '—';
                                }

                                $freqStr = $frequency instanceof \BackedEnum ? (string) $frequency->value : (string) $frequency;
                                $configArr = is_object($config) ? json_decode(json_encode($config), true) : (is_array($config) ? $config : []);

                                if ($freqStr === 'daily') {
                                    return 'Repeats daily';
                                }

                                if ($freqStr === 'weekly') {
                                    $days = array_map('ucfirst', $configArr['days'] ?? []);

                                    return 'Repeats weekly'.(! empty($days) ? ' on '.implode(', ', $days) : '');
                                }

                                if ($freqStr === 'biweekly') {
                                    $days = array_map('ucfirst', $configArr['days'] ?? []);

                                    return 'Repeats bi-weekly'.(! empty($days) ? ' on '.implode(', ', $days) : '');
                                }

                                if (preg_match('/^every_(\d+)_weeks$/', $freqStr, $matches)) {
                                    $weeks = $matches[1];
                                    $days = array_map('ucfirst', $configArr['days'] ?? []);

                                    return "Repeats every {$weeks} weeks".(! empty($days) ? ' on '.implode(', ', $days) : '');
                                }

                                if ($freqStr === 'monthly') {
                                    $days = $configArr['days_of_month'] ?? [];

                                    return 'Repeats monthly'.(! empty($days) ? ' on day(s) '.implode(', ', $days) : '');
                                }

                                if ($freqStr === 'bimonthly') {
                                    $days = $configArr['days_of_month'] ?? [];

                                    return 'Repeats every 2 months'.(! empty($days) ? ' on day(s) '.implode(', ', $days) : '');
                                }

                                if ($freqStr === 'quarterly') {
                                    $days = $configArr['days_of_month'] ?? [];

                                    return 'Repeats quarterly'.(! empty($days) ? ' on day(s) '.implode(', ', $days) : '');
                                }

                                if ($freqStr === 'semiannually') {
                                    $days = $configArr['days_of_month'] ?? [];

                                    return 'Repeats every 6 months'.(! empty($days) ? ' on day(s) '.implode(', ', $days) : '');
                                }

                                if (preg_match('/^every_(\d+)_months$/', $freqStr, $matches)) {
                                    $months = $matches[1];
                                    $days = $configArr['days_of_month'] ?? [];

                                    return "Repeats every {$months} months".(! empty($days) ? ' on day(s) '.implode(', ', $days) : '');
                                }

                                if ($freqStr === 'annually') {
                                    $months = array_map('ucfirst', $record->metadata['recurring_months'] ?? []);

                                    return 'Repeats annually'.(! empty($months) ? ' in '.implode(', ', $months) : '');
                                }

                                if ($freqStr === 'monthly_ordinal_weekday') {
                                    $ordinal = $configArr['ordinal'] ?? 1;
                                    $dayName = ucfirst($configArr['day'] ?? 'Monday');
                                    $ordinalWords = [1 => 'first', 2 => 'second', 3 => 'third', 4 => 'fourth', 5 => 'last'];
                                    $ordinalWord = $ordinalWords[$ordinal] ?? 'first';

                                    return "Repeats monthly on the {$ordinalWord} {$dayName}";
                                }

                                return $freqStr;
                            })
                            ->placeholder('—'),
                        TextEntry::make('end_type_summary')
                            ->label('Ends')
                            ->visible(fn ($record): bool => (bool) $record->is_recurring)
                            ->state(function ($record): string {
                                $endType = $record->metadata['end_type'] ?? null;
                                if ($endType === 'after_occurrences') {
                                    $occurrences = $record->metadata['occurrences'] ?? 10;

                                    return "After {$occurrences} occurrences";
                                }
                                if ($record->end_date) {
                                    return 'On '.Carbon::parse($record->end_date)->format('M j, Y');
                                }

                                return 'Never';
                            })
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
                    ->visible(fn ($record): bool => ($record->schedule_type->value ?? '') === ScheduleTypes::AVAILABILITY->value),

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
                    ->visible(fn ($record): bool => ($record->schedule_type->value ?? '') === ScheduleTypes::APPOINTMENT->value),

                Section::make('Blocked Time')
                    ->schema([
                        TextEntry::make('metadata.reason')
                            ->label('Reason')
                            ->placeholder('No reason provided')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record): bool => ($record->schedule_type->value ?? '') === ScheduleTypes::BLOCKED->value),

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
