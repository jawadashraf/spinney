<?php

declare(strict_types=1);

namespace App\Filament\Resources\MySchedules\Schemas;

use Carbon\Carbon;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Zap\Enums\ScheduleTypes;

final class MyScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Shift Overview')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->default('Volunteer Shift')
                            ->maxLength(255),

                        Select::make('schedule_type')
                            ->options([
                                ScheduleTypes::AVAILABILITY->value => 'Availability (Work Shift)',
                                ScheduleTypes::BLOCKED->value => 'Time Off / Unavailable',
                            ])
                            ->default(ScheduleTypes::AVAILABILITY->value)
                            ->required(),

                        DatePicker::make('start_date')
                            ->required()
                            ->default(now())
                            ->live(),

                        DatePicker::make('end_date')
                            ->nullable(),

                        Toggle::make('is_recurring')
                            ->default(true)
                            ->live(),

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
                            ->default(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'])
                            ->inline()
                            ->required(fn (Get $get): bool => $get('is_recurring') === true)
                            ->visible(fn (Get $get): bool => $get('is_recurring') === true)
                            ->rule(function (Get $get) {
                                return function (string $attribute, $value, Closure $fail) use ($get) {
                                    $startDate = $get('start_date');
                                    $endDate = $get('end_date');

                                    if (! $startDate || ! $endDate || ! $get('is_recurring')) {
                                        return;
                                    }

                                    $start = Carbon::parse($startDate);
                                    $end = Carbon::parse($endDate);

                                    if ($start->diffInDays($end) >= 6) {
                                        return;
                                    }

                                    $rangeDays = [];
                                    for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                                        $rangeDays[] = strtolower($date->format('l'));
                                    }

                                    $selectedDays = (array) $value;
                                    $invalidDays = array_diff($selectedDays, $rangeDays);

                                    if (! empty($invalidDays)) {
                                        $invalidDaysList = collect($invalidDays)->map(fn ($d) => ucfirst(substr($d, 0, 3)))->join(', ');
                                        $fail("The selected weekdays ({$invalidDaysList}) do not fall within the date range ({$start->format('M j')} - {$end->format('M j')}).");
                                    }
                                };
                            })
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make('Shift Timings')
                    ->schema([
                        Repeater::make('periods')
                            ->relationship('periods')
                            ->schema([
                                TimePicker::make('start_time')
                                    ->required()
                                    ->seconds(false),

                                TimePicker::make('end_time')
                                    ->required()
                                    ->seconds(false),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->required()
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data, Get $get): array {
                                $startDate = $get('start_date') ?? now()->toDateString();
                                $data['date'] = is_string($startDate) ? substr($startDate, 0, 10) : now()->toDateString();
                                $data['is_available'] = true;

                                return $data;
                            })
                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data, Get $get): array {
                                if (empty($data['date'])) {
                                    $startDate = $get('start_date') ?? now()->toDateString();
                                    $data['date'] = is_string($startDate) ? substr($startDate, 0, 10) : now()->toDateString();
                                }
                                $data['is_available'] = true;

                                return $data;
                            }),
                    ]),
            ]);
    }
}
