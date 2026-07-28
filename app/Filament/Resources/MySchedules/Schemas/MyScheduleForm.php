<?php

declare(strict_types=1);

namespace App\Filament\Resources\MySchedules\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
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
