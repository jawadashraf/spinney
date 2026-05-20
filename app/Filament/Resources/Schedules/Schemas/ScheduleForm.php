<?php

declare(strict_types=1);

namespace App\Filament\Resources\Schedules\Schemas;

use App\Enums\AttendeeType;
use App\Enums\CounselorType;
use App\Enums\PaymentType;
use App\Enums\ScheduleType;
use App\Enums\SessionType;
use App\Models\People;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Relations\Relation;
use Zap\Data\DailyFrequencyConfig;
use Zap\Data\MonthlyFrequencyConfig\AnnuallyFrequencyConfig;
use Zap\Data\MonthlyFrequencyConfig\BiMonthlyFrequencyConfig;
use Zap\Data\MonthlyFrequencyConfig\EveryXMonthsFrequencyConfig;
use Zap\Data\MonthlyFrequencyConfig\MonthlyFrequencyConfig;
use Zap\Data\MonthlyFrequencyConfig\MonthlyOrdinalWeekdayFrequencyConfig;
use Zap\Data\MonthlyFrequencyConfig\QuarterlyFrequencyConfig;
use Zap\Data\MonthlyFrequencyConfig\SemiAnnuallyFrequencyConfig;
use Zap\Data\WeeklyFrequencyConfig\BiWeeklyFrequencyConfig;
use Zap\Data\WeeklyFrequencyConfig\EveryXWeeksFrequencyConfig;
use Zap\Data\WeeklyFrequencyConfig\WeeklyFrequencyConfig;
use Zap\Enums\Frequency;
use Zap\Models\Schedule;

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
                            ->options(
                                collect(ScheduleType::cases())
                                    ->reject(fn (ScheduleType $type) => $type === ScheduleType::APPOINTMENT)
                                    ->mapWithKeys(fn (ScheduleType $type) => [$type->value => $type->getLabel()])
                                    ->toArray()
                            )
                            ->required()
                            ->default(ScheduleType::AVAILABILITY->value)
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
                        Grid::make([
                            'default' => 1,
                            'lg' => 3,
                        ])
                            ->schema([
                                // Left Column: All scheduler settings inputs
                                Grid::make(2)
                                    ->schema([
                                        DateTimePicker::make('start_date')
                                            ->required()
                                            ->native(false)
                                            ->default(now())
                                            ->live(),
                                        Select::make('metadata.timezone')
                                            ->options(self::getTimezoneOptions())
                                            ->default(config('app.timezone', 'UTC'))
                                            ->native(false)
                                            ->searchable()
                                            ->required()
                                            ->live(),
                                        Toggle::make('is_recurring')
                                            ->default(false)
                                            ->live()
                                            ->label('Recurring Schedule')
                                            ->columnSpanFull(),

                                        // Recurring Options Group
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('repeat_interval')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->minValue(1)
                                                    ->required()
                                                    ->live()
                                                    ->label('Repeat every'),
                                                Select::make('repeat_unit')
                                                    ->options([
                                                        'day' => 'days',
                                                        'week' => 'weeks',
                                                        'month' => 'months',
                                                        'year' => 'years',
                                                    ])
                                                    ->default('week')
                                                    ->required()
                                                    ->live()
                                                    ->label(' '),

                                                // Weekly Day Selection
                                                ToggleButtons::make('days_of_week')
                                                    ->multiple()
                                                    ->options([
                                                        'monday' => 'Mon',
                                                        'tuesday' => 'Tue',
                                                        'wednesday' => 'Wed',
                                                        'thursday' => 'Thu',
                                                        'friday' => 'Fri',
                                                        'saturday' => 'Sat',
                                                        'sunday' => 'Sun',
                                                    ])
                                                    ->inline()
                                                    ->required()
                                                    ->live()
                                                    ->columnSpanFull()
                                                    ->visible(fn (Get $get): bool => $get('repeat_unit') === 'week'),

                                                // Monthly Options
                                                Grid::make(1)
                                                    ->schema([
                                                        ToggleButtons::make('month_repeat_by')
                                                            ->options([
                                                                'day_of_month' => 'On specific days',
                                                                'day_of_week' => 'On specific weekdays',
                                                            ])
                                                            ->default('day_of_month')
                                                            ->inline()
                                                            ->live()
                                                            ->label('Repeat by'),

                                                        // Day of month buttons (Circular style grid)
                                                        ToggleButtons::make('days_of_month')
                                                            ->multiple()
                                                            ->options(array_combine(range(1, 31), range(1, 31)))
                                                            ->columns(7)
                                                            ->required()
                                                            ->live()
                                                            ->visible(fn (Get $get): bool => $get('month_repeat_by') === 'day_of_month'),

                                                        // Day of week ordinals
                                                        Grid::make(2)
                                                            ->schema([
                                                                Select::make('ordinal')
                                                                    ->options([
                                                                        1 => 'First',
                                                                        2 => 'Second',
                                                                        3 => 'Third',
                                                                        4 => 'Fourth',
                                                                        5 => 'Last',
                                                                    ])
                                                                    ->default(1)
                                                                    ->required()
                                                                    ->live()
                                                                    ->native(false)
                                                                    ->label('On the'),
                                                                Select::make('day_of_week_name')
                                                                    ->options([
                                                                        'monday' => 'Monday',
                                                                        'tuesday' => 'Tuesday',
                                                                        'wednesday' => 'Wednesday',
                                                                        'thursday' => 'Thursday',
                                                                        'friday' => 'Friday',
                                                                        'saturday' => 'Saturday',
                                                                        'sunday' => 'Sunday',
                                                                    ])
                                                                    ->default('monday')
                                                                    ->required()
                                                                    ->live()
                                                                    ->native(false)
                                                                    ->label('Weekday'),
                                                            ])
                                                            ->visible(fn (Get $get): bool => $get('month_repeat_by') === 'day_of_week'),
                                                    ])
                                                    ->columnSpanFull()
                                                    ->visible(fn (Get $get): bool => $get('repeat_unit') === 'month'),

                                                // Yearly Option (Month Selector)
                                                ToggleButtons::make('months')
                                                    ->multiple()
                                                    ->options([
                                                        'january' => 'Jan',
                                                        'february' => 'Feb',
                                                        'march' => 'Mar',
                                                        'april' => 'Apr',
                                                        'may' => 'May',
                                                        'june' => 'Jun',
                                                        'july' => 'Jul',
                                                        'august' => 'Aug',
                                                        'september' => 'Sep',
                                                        'october' => 'Oct',
                                                        'november' => 'Nov',
                                                        'december' => 'Dec',
                                                    ])
                                                    ->inline()
                                                    ->required()
                                                    ->live()
                                                    ->columns(6)
                                                    ->columnSpanFull()
                                                    ->visible(fn (Get $get): bool => $get('repeat_unit') === 'year'),

                                                // End Recurrence Options
                                                Grid::make(1)
                                                    ->schema([
                                                        ToggleButtons::make('end_type')
                                                            ->options([
                                                                'never' => 'Never',
                                                                'on_date' => 'On Date',
                                                                'after_occurrences' => 'After occurrences',
                                                            ])
                                                            ->default('never')
                                                            ->inline()
                                                            ->live()
                                                            ->label('Ends'),

                                                        DatePicker::make('end_date')
                                                            ->native(false)
                                                            ->required()
                                                            ->live()
                                                            ->visible(fn (Get $get): bool => $get('end_type') === 'on_date')
                                                            ->label('End Date'),

                                                        TextInput::make('occurrences')
                                                            ->numeric()
                                                            ->default(10)
                                                            ->minValue(1)
                                                            ->required()
                                                            ->live()
                                                            ->visible(fn (Get $get): bool => $get('end_type') === 'after_occurrences')
                                                            ->label('Number of occurrences'),
                                                    ])
                                                    ->columnSpanFull(),
                                            ])
                                            ->visible(fn (Get $get): bool => $get('is_recurring') === true)
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpan([
                                        'default' => 1,
                                        'lg' => 2,
                                    ]),

                                // Right Column: Live occurrence calculation and preview
                                Placeholder::make('recurrence_preview')
                                    ->label('')
                                    ->content(fn (Get $get) => view('filament.schedules.recurrence-preview', self::getRecurrencePreviewData($get)))
                                    ->columnSpan([
                                        'default' => 1,
                                        'lg' => 1,
                                    ]),
                            ]),
                    ])
                    ->visible(fn (Get $get): bool => ($get('schedule_type') ?? '') !== ScheduleType::APPOINTMENT->value),

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
                    ->visible(fn (Get $get): bool => filled($get('schedule_type')) && ($get('schedule_type') ?? '') !== ScheduleType::APPOINTMENT->value)
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
                        DatePicker::make('booking_date')
                            ->label('Booking Date')
                            ->required(fn (Get $get): bool => ($get('schedule_type') ?? '') === ScheduleType::APPOINTMENT->value)
                            ->live()
                            ->native(false)
                            ->minDate(now()->toDateString())
                            ->visible(fn (Get $get): bool => ($get('schedule_type') ?? '') === ScheduleType::APPOINTMENT->value),
                        Select::make('selected_slot')
                            ->label('Available 60-Minute Slots')
                            ->required(fn (Get $get): bool => ($get('schedule_type') ?? '') === ScheduleType::APPOINTMENT->value)
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
                            ->visible(fn (Get $get): bool => ($get('schedule_type') ?? '') === ScheduleType::APPOINTMENT->value && filled($get('booking_date'))),
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

    public static function getTimezoneOptions(): array
    {
        $timezones = \DateTimeZone::listIdentifiers();

        return array_combine($timezones, $timezones);
    }

    public static function getRecurrencePreviewData(Get $get): array
    {
        $startDateStr = $get('start_date');
        if (! $startDateStr) {
            return [
                'is_valid' => false,
            ];
        }

        $startDate = Carbon::parse($startDateStr);
        $isRecurring = (bool) $get('is_recurring');
        $timezone = $get('metadata.timezone') ?? config('app.timezone', 'UTC');

        if (! $isRecurring) {
            return [
                'is_valid' => true,
                'is_recurring' => false,
                'summary' => 'Runs once on '.$startDate->format('l, F j, Y').' at '.$startDate->format('g:i A').'.',
                'timezone' => $timezone,
                'occurrences' => [$startDate],
            ];
        }

        $repeatInterval = (int) ($get('repeat_interval') ?? 1);
        $repeatUnit = $get('repeat_unit') ?? 'week';
        $summary = '';
        $occurrences = [];

        $frequency = null;
        $config = [];

        if ($repeatUnit === 'day') {
            $frequency = 'daily';
            $summary = $repeatInterval === 1 ? 'Repeats daily' : "Repeats every {$repeatInterval} days";
            $config = [];
        } elseif ($repeatUnit === 'week') {
            $days = $get('days_of_week') ?? [];
            if (empty($days)) {
                $days = [strtolower($startDate->format('l'))];
            }
            if ($repeatInterval === 1) {
                $frequency = 'weekly';
            } elseif ($repeatInterval === 2) {
                $frequency = 'biweekly';
            } else {
                $frequency = "every_{$repeatInterval}_weeks";
            }
            $config = [
                'days' => $days,
                'startsOn' => $startDate->toDateString(),
            ];
            $summary = ($repeatInterval === 1 ? 'Repeats weekly' : "Repeats every {$repeatInterval} weeks").' on '.implode(', ', array_map('ucfirst', $days));
        } elseif ($repeatUnit === 'month') {
            $monthRepeatBy = $get('month_repeat_by') ?? 'day_of_month';
            if ($monthRepeatBy === 'day_of_month') {
                $daysOfMonth = array_map('intval', $get('days_of_month') ?? []);
                if (empty($daysOfMonth)) {
                    $daysOfMonth = [$startDate->day];
                }
                if ($repeatInterval === 1) {
                    $frequency = 'monthly';
                } elseif ($repeatInterval === 2) {
                    $frequency = 'bimonthly';
                } elseif ($repeatInterval === 3) {
                    $frequency = 'quarterly';
                } elseif ($repeatInterval === 6) {
                    $frequency = 'semiannually';
                } else {
                    $frequency = "every_{$repeatInterval}_months";
                }
                $config = [
                    'days_of_month' => $daysOfMonth,
                    'start_month' => $startDate->month,
                ];
                $summary = ($repeatInterval === 1 ? 'Repeats monthly' : "Repeats every {$repeatInterval} months").' on day(s) '.implode(', ', $daysOfMonth);
            } else {
                $frequency = 'monthly_ordinal_weekday';
                $ordinal = (int) ($get('ordinal') ?? 1);
                $dayName = $get('day_of_week_name') ?? strtolower($startDate->format('l'));

                $dayOfWeekMap = [
                    'sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
                    'thursday' => 4, 'friday' => 5, 'saturday' => 6,
                ];
                $dayOfWeek = $dayOfWeekMap[$dayName] ?? 1;

                $config = [
                    'ordinal' => $ordinal,
                    'dayOfWeek' => $dayOfWeek,
                    'day' => $dayName,
                ];
                $ordinalWords = [1 => 'first', 2 => 'second', 3 => 'third', 4 => 'fourth', 5 => 'last'];
                $ordinalWord = $ordinalWords[$ordinal] ?? 'first';
                $summary = "Repeats monthly on the {$ordinalWord} ".ucfirst($dayName);
            }
        } elseif ($repeatUnit === 'year') {
            $months = $get('months') ?? [];
            if (empty($months)) {
                $months = [strtolower($startDate->format('F'))];
            }
            $frequency = 'annually';
            $config = [
                'days_of_month' => [$startDate->day],
                'start_month' => $startDate->month,
            ];
            $summary = ($repeatInterval === 1 ? 'Repeats annually' : "Repeats every {$repeatInterval} years").' in '.implode(', ', array_map('ucfirst', $months));
        }

        $summary .= ', starting '.$startDate->format('M j, Y').' at '.$startDate->format('g:i A');

        $endType = $get('end_type') ?? 'never';
        if ($endType === 'never') {
            $summary .= ' (Never ends).';
        } elseif ($endType === 'on_date') {
            $endDateVal = $get('end_date');
            if ($endDateVal) {
                $summary .= ' (Ends on '.Carbon::parse($endDateVal)->format('M j, Y').').';
            }
        } elseif ($endType === 'after_occurrences') {
            $occCount = (int) ($get('occurrences') ?? 10);
            $summary .= " (Ends after {$occCount} occurrences).";
        }

        $configInstance = null;
        if ($frequency === 'daily') {
            $configInstance = new DailyFrequencyConfig;
        } elseif (preg_match('/^every_(\d+)_weeks$/', $frequency, $matches)) {
            $configInstance = EveryXWeeksFrequencyConfig::fromArray(
                array_merge($config, ['frequencyWeeks' => (int) $matches[1]])
            );
        } elseif ($frequency === 'weekly') {
            $configInstance = WeeklyFrequencyConfig::fromArray($config);
        } elseif ($frequency === 'biweekly') {
            $configInstance = BiWeeklyFrequencyConfig::fromArray($config);
        } elseif (preg_match('/^every_(\d+)_months$/', $frequency, $matches)) {
            $configInstance = EveryXMonthsFrequencyConfig::fromArray(
                array_merge($config, ['frequencyMonths' => (int) $matches[1]])
            );
        } elseif ($frequency === 'monthly') {
            $configInstance = MonthlyFrequencyConfig::fromArray($config);
        } elseif ($frequency === 'bimonthly') {
            $configInstance = BiMonthlyFrequencyConfig::fromArray($config);
        } elseif ($frequency === 'quarterly') {
            $configInstance = QuarterlyFrequencyConfig::fromArray($config);
        } elseif ($frequency === 'semiannually') {
            $configInstance = SemiAnnuallyFrequencyConfig::fromArray($config);
        } elseif ($frequency === 'annually') {
            $configInstance = AnnuallyFrequencyConfig::fromArray($config);
        } elseif ($frequency === 'monthly_ordinal_weekday') {
            $configInstance = MonthlyOrdinalWeekdayFrequencyConfig::fromArray($config);
        }

        if ($configInstance) {
            try {
                $configInstance->setStartFromStartDate($startDate);

                $loopDate = $startDate->copy();
                if ($configInstance->shouldCreateInstance($loopDate)) {
                    $occurrences[] = $loopDate->copy();
                }

                $safetyLimit = 0;
                while (count($occurrences) < 4 && $safetyLimit < 500) {
                    $loopDate = $configInstance->getNextRecurrence($loopDate);
                    $occurrences[] = $loopDate->copy();
                    $safetyLimit++;
                }
            } catch (\Throwable $e) {
            }
        }

        return [
            'is_valid' => true,
            'is_recurring' => true,
            'summary' => $summary,
            'timezone' => $timezone,
            'occurrences' => $occurrences,
        ];
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['schedule_type'] ?? '') === (ScheduleType::APPOINTMENT->value ?? ScheduleType::APPOINTMENT)) {
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

        if (! ($data['is_recurring'] ?? false)) {
            $data['frequency'] = null;
            $data['frequency_config'] = null;

            return $data;
        }

        $repeatInterval = (int) ($data['repeat_interval'] ?? 1);
        $repeatUnit = $data['repeat_unit'] ?? 'week';
        $startDate = isset($data['start_date']) ? Carbon::parse($data['start_date']) : now();

        $frequency = null;
        $config = [];

        if ($repeatUnit === 'day') {
            $frequency = 'daily';
            $config = [];
        } elseif ($repeatUnit === 'week') {
            $days = $data['days_of_week'] ?? [];
            if (empty($days)) {
                $days = [strtolower($startDate->format('l'))];
            }
            if ($repeatInterval === 1) {
                $frequency = 'weekly';
            } elseif ($repeatInterval === 2) {
                $frequency = 'biweekly';
            } else {
                $frequency = "every_{$repeatInterval}_weeks";
            }
            $config = [
                'days' => $days,
                'startsOn' => $startDate->toDateString(),
            ];
        } elseif ($repeatUnit === 'month') {
            $monthRepeatBy = $data['month_repeat_by'] ?? 'day_of_month';
            if ($monthRepeatBy === 'day_of_month') {
                $daysOfMonth = array_map('intval', $data['days_of_month'] ?? []);
                if (empty($daysOfMonth)) {
                    $daysOfMonth = [$startDate->day];
                }
                if ($repeatInterval === 1) {
                    $frequency = 'monthly';
                } elseif ($repeatInterval === 2) {
                    $frequency = 'bimonthly';
                } elseif ($repeatInterval === 3) {
                    $frequency = 'quarterly';
                } elseif ($repeatInterval === 6) {
                    $frequency = 'semiannually';
                } else {
                    $frequency = "every_{$repeatInterval}_months";
                }
                $config = [
                    'days_of_month' => $daysOfMonth,
                    'start_month' => $startDate->month,
                ];
            } else {
                $frequency = 'monthly_ordinal_weekday';
                $ordinal = (int) ($data['ordinal'] ?? 1);
                $dayName = $data['day_of_week_name'] ?? strtolower($startDate->format('l'));

                $dayOfWeekMap = [
                    'sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
                    'thursday' => 4, 'friday' => 5, 'saturday' => 6,
                ];
                $dayOfWeek = $dayOfWeekMap[$dayName] ?? 1;

                $config = [
                    'ordinal' => $ordinal,
                    'dayOfWeek' => $dayOfWeek,
                    'day' => $dayName,
                ];
            }
        } elseif ($repeatUnit === 'year') {
            $months = $data['months'] ?? [];
            if (empty($months)) {
                $months = [strtolower($startDate->format('F'))];
            }

            $frequency = 'annually';
            $config = [
                'days_of_month' => [$startDate->day],
                'start_month' => $startDate->month,
            ];

            $data['metadata']['recurring_months'] = $months;
        }

        $data['frequency'] = $frequency;
        $data['frequency_config'] = $config;

        $endType = $data['end_type'] ?? 'never';
        if ($endType === 'never') {
            $data['end_date'] = null;
        } elseif ($endType === 'on_date') {
        } elseif ($endType === 'after_occurrences') {
            $occurrencesCount = (int) ($data['occurrences'] ?? 10);
            $computedEndDate = self::computeEndDateFromOccurrences($startDate, $frequency, $config, $occurrencesCount);
            $data['end_date'] = $computedEndDate?->toDateString();

            $data['metadata']['end_type'] = 'after_occurrences';
            $data['metadata']['occurrences'] = $occurrencesCount;
        }

        return $data;
    }

    public static function computeEndDateFromOccurrences(Carbon $startDate, ?string $frequency, ?array $config, int $count): ?Carbon
    {
        if (! $frequency || ! $config) {
            return null;
        }

        $configInstance = null;
        if ($frequency === 'daily') {
            $configInstance = new DailyFrequencyConfig;
        } elseif (preg_match('/^every_(\d+)_weeks$/', $frequency, $matches)) {
            $configInstance = EveryXWeeksFrequencyConfig::fromArray(
                array_merge($config, ['frequencyWeeks' => (int) $matches[1]])
            );
        } elseif ($frequency === 'weekly') {
            $configInstance = WeeklyFrequencyConfig::fromArray($config);
        } elseif ($frequency === 'biweekly') {
            $configInstance = BiWeeklyFrequencyConfig::fromArray($config);
        } elseif (preg_match('/^every_(\d+)_months$/', $frequency, $matches)) {
            $configInstance = EveryXMonthsFrequencyConfig::fromArray(
                array_merge($config, ['frequencyMonths' => (int) $matches[1]])
            );
        } elseif ($frequency === 'monthly') {
            $configInstance = MonthlyFrequencyConfig::fromArray($config);
        } elseif ($frequency === 'bimonthly') {
            $configInstance = BiMonthlyFrequencyConfig::fromArray($config);
        } elseif ($frequency === 'quarterly') {
            $configInstance = QuarterlyFrequencyConfig::fromArray($config);
        } elseif ($frequency === 'semiannually') {
            $configInstance = SemiAnnuallyFrequencyConfig::fromArray($config);
        } elseif ($frequency === 'annually') {
            $configInstance = AnnuallyFrequencyConfig::fromArray($config);
        } elseif ($frequency === 'monthly_ordinal_weekday') {
            $configInstance = MonthlyOrdinalWeekdayFrequencyConfig::fromArray($config);
        }

        if (! $configInstance) {
            return null;
        }

        $configInstance->setStartFromStartDate($startDate);

        $occurrences = [];
        $loopDate = $startDate->copy();

        if ($configInstance->shouldCreateInstance($loopDate)) {
            $occurrences[] = $loopDate->copy();
        }

        $safetyLimit = 0;
        while (count($occurrences) < $count && $safetyLimit < 500) {
            $loopDate = $configInstance->getNextRecurrence($loopDate);
            $occurrences[] = $loopDate->copy();
            $safetyLimit++;
        }

        return ! empty($occurrences) ? end($occurrences) : null;
    }

    public static function fillFormFromRecord(Schedule $record): array
    {
        $data = [];
        if (($record->schedule_type->value ?? $record->schedule_type) === (ScheduleType::APPOINTMENT->value ?? ScheduleType::APPOINTMENT)) {
            $data['booking_date'] = $record->start_date ? Carbon::parse($record->start_date)->toDateString() : null;
            $meta = $record->metadata ?? [];
            if (isset($meta['start_time']) && isset($meta['end_time'])) {
                $data['selected_slot'] = "{$meta['start_time']}-{$meta['end_time']}";
            }
            $data['is_recurring'] = false;

            return $data;
        }

        if (! $record->is_recurring) {
            $data['is_recurring'] = false;

            return $data;
        }

        $data['is_recurring'] = true;
        $frequency = $record->frequency;
        $config = $record->frequency_config;

        $repeatInterval = 1;
        $repeatUnit = 'week';
        $daysOfWeek = [];
        $monthRepeatBy = 'day_of_month';
        $daysOfMonth = [];
        $ordinal = 1;
        $dayOfWeekName = 'monday';
        $months = [];

        if (is_string($frequency)) {
            if ($frequency === 'daily') {
                $repeatUnit = 'day';
                $repeatInterval = 1;
            } elseif (preg_match('/^every_(\d+)_weeks$/', $frequency, $matches)) {
                $repeatUnit = 'week';
                $repeatInterval = (int) $matches[1];
                $daysOfWeek = $config->days ?? [];
            } elseif ($frequency === 'weekly') {
                $repeatUnit = 'week';
                $repeatInterval = 1;
                $daysOfWeek = $config->days ?? [];
            } elseif ($frequency === 'biweekly') {
                $repeatUnit = 'week';
                $repeatInterval = 2;
                $daysOfWeek = $config->days ?? [];
            } elseif (preg_match('/^every_(\d+)_months$/', $frequency, $matches)) {
                $repeatUnit = 'month';
                $repeatInterval = (int) $matches[1];
                $monthRepeatBy = 'day_of_month';
                $daysOfMonth = $config->days_of_month ?? [];
            } elseif ($frequency === 'monthly') {
                $repeatUnit = 'month';
                $repeatInterval = 1;
                $monthRepeatBy = 'day_of_month';
                $daysOfMonth = $config->days_of_month ?? [];
            } elseif ($frequency === 'bimonthly') {
                $repeatUnit = 'month';
                $repeatInterval = 2;
                $monthRepeatBy = 'day_of_month';
                $daysOfMonth = $config->days_of_month ?? [];
            } elseif ($frequency === 'quarterly') {
                $repeatUnit = 'month';
                $repeatInterval = 3;
                $monthRepeatBy = 'day_of_month';
                $daysOfMonth = $config->days_of_month ?? [];
            } elseif ($frequency === 'semiannually') {
                $repeatUnit = 'month';
                $repeatInterval = 6;
                $monthRepeatBy = 'day_of_month';
                $daysOfMonth = $config->days_of_month ?? [];
            } elseif ($frequency === 'annually') {
                $repeatUnit = 'year';
                $repeatInterval = 1;
                $months = $record->metadata['recurring_months'] ?? [];
            } elseif ($frequency === 'monthly_ordinal_weekday') {
                $repeatUnit = 'month';
                $repeatInterval = 1;
                $monthRepeatBy = 'day_of_week';
                $ordinal = $config->ordinal ?? 1;
                $dayOfWeekName = $config->day ?? 'monday';
            }
        } elseif ($frequency instanceof Frequency) {
            $val = $frequency->value;
            if ($val === 'daily') {
                $repeatUnit = 'day';
                $repeatInterval = 1;
            } elseif ($val === 'weekly') {
                $repeatUnit = 'week';
                $repeatInterval = 1;
                $daysOfWeek = $config->days ?? [];
            } elseif ($val === 'biweekly') {
                $repeatUnit = 'week';
                $repeatInterval = 2;
                $daysOfWeek = $config->days ?? [];
            } elseif ($val === 'monthly') {
                $repeatUnit = 'month';
                $repeatInterval = 1;
                $daysOfMonth = $config->days_of_month ?? [];
            } elseif ($val === 'bimonthly') {
                $repeatUnit = 'month';
                $repeatInterval = 2;
                $daysOfMonth = $config->days_of_month ?? [];
            } elseif ($val === 'quarterly') {
                $repeatUnit = 'month';
                $repeatInterval = 3;
                $daysOfMonth = $config->days_of_month ?? [];
            } elseif ($val === 'semiannually') {
                $repeatUnit = 'month';
                $repeatInterval = 6;
                $daysOfMonth = $config->days_of_month ?? [];
            } elseif ($val === 'annually') {
                $repeatUnit = 'year';
                $repeatInterval = 1;
                $months = $record->metadata['recurring_months'] ?? [];
            }
        }

        $data['repeat_interval'] = $repeatInterval;
        $data['repeat_unit'] = $repeatUnit;
        $data['days_of_week'] = $daysOfWeek;
        $data['month_repeat_by'] = $monthRepeatBy;
        $data['days_of_month'] = $daysOfMonth;
        $data['ordinal'] = $ordinal;
        $data['day_of_week_name'] = $dayOfWeekName;
        $data['months'] = $months;

        $endType = 'never';
        $occurrencesCount = 10;

        if ($record->metadata['end_type'] ?? null) {
            $endType = $record->metadata['end_type'];
            $occurrencesCount = $record->metadata['occurrences'] ?? 10;
        } elseif ($record->end_date) {
            $endType = 'on_date';
        }

        $data['end_type'] = $endType;
        $data['occurrences'] = $occurrencesCount;

        return $data;
    }
}
