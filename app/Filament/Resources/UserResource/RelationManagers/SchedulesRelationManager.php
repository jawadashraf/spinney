<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\RelationManagers;

use Carbon\Carbon;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Zap\Enums\Frequency;
use Zap\Enums\ScheduleTypes;
use Zap\Models\Schedule;

final class SchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'schedules';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Schedule Overview')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->default('Weekly Volunteer Shift')
                            ->maxLength(255),

                        Select::make('schedule_type')
                            ->options([
                                ScheduleTypes::AVAILABILITY->value => 'Availability (Work Hours)',
                                ScheduleTypes::BLOCKED->value => 'Blocked Time',
                                ScheduleTypes::APPOINTMENT->value => 'Appointment',
                                ScheduleTypes::CUSTOM->value => 'Custom',
                            ])
                            ->default(ScheduleTypes::AVAILABILITY->value)
                            ->required(),

                        DatePicker::make('start_date')
                            ->required()
                            ->default(now()),

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
                    ]),

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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('schedule_type')
                    ->badge()
                    ->color(fn (string|ScheduleTypes|null $state): string => match ($state instanceof ScheduleTypes ? $state->value : (string) $state) {
                        'availability' => 'success',
                        'blocked' => 'danger',
                        default => 'gray',
                    }),

                IconColumn::make('is_recurring')
                    ->label('Recurring')
                    ->boolean(),

                TextColumn::make('shift_dates')
                    ->label('Dates')
                    ->state(function (Schedule $record): string {
                        $start = $record->start_date ? $record->start_date->format('M j, Y') : '';
                        $end = $record->end_date ? ' - '.$record->end_date->format('M j, Y') : '';

                        return $start.$end;
                    }),

                TextColumn::make('days')
                    ->label('Days')
                    ->state(function (Schedule $record): string {
                        if (! $record->is_recurring) {
                            return 'N/A';
                        }
                        $config = is_array($record->frequency_config) ? $record->frequency_config : (method_exists($record->frequency_config, 'toArray') ? $record->frequency_config->toArray() : []);
                        $days = $config['days'] ?? [];

                        return collect($days)->map(fn ($day) => ucfirst(substr($day, 0, 3)))->join(', ');
                    }),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('approval_status')
                    ->label('Approval Status')
                    ->badge()
                    ->state(fn (Schedule $record): string => ($record->metadata['is_approved'] ?? false) ? 'Approved' : 'Pending Approval')
                    ->color(fn (string $state): string => $state === 'Approved' ? 'success' : 'warning'),

                TextColumn::make('periods_summary')
                    ->label('Shift Timings')
                    ->state(fn (Schedule $record): string => $record->periods
                        ->map(fn ($period): string => "{$period->start_time} - {$period->end_time}")
                        ->join(', ')
                    ),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        if (! empty($data['is_recurring'])) {
                            $data['frequency'] = Frequency::WEEKLY->value;
                            $data['frequency_config'] = [
                                'days' => $data['days_of_week'] ?? ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                                'startsOn' => is_string($data['start_date'] ?? null) ? substr($data['start_date'], 0, 10) : now()->toDateString(),
                            ];
                        }
                        unset($data['days_of_week']);

                        return $data;
                    }),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(fn (Schedule $record): string => ($record->metadata['is_approved'] ?? false) ? 'Unapprove' : 'Approve')
                    ->icon(fn (Schedule $record): string => ($record->metadata['is_approved'] ?? false) ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (Schedule $record): string => ($record->metadata['is_approved'] ?? false) ? 'danger' : 'success')
                    ->visible(fn (): bool => auth()->user()?->hasAnyRole(['admin', 'manager']) ?? false)
                    ->action(function (Schedule $record): void {
                        $meta = $record->metadata ?? [];
                        $meta['is_approved'] = ! ($meta['is_approved'] ?? false);
                        $record->update(['metadata' => $meta]);
                    }),
                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data): array {
                        if (! empty($data['is_recurring']) && isset($data['frequency_config']['days'])) {
                            $data['days_of_week'] = $data['frequency_config']['days'];
                        }

                        return $data;
                    })
                    ->mutateFormDataUsing(function (array $data, Schedule $record): array {
                        if (! empty($data['is_recurring'])) {
                            $data['frequency'] = Frequency::WEEKLY->value;

                            $config = is_array($record->frequency_config) ? $record->frequency_config : (method_exists($record->frequency_config, 'toArray') ? $record->frequency_config->toArray() : []);
                            $config['days'] = $data['days_of_week'] ?? ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
                            $config['startsOn'] = is_string($data['start_date'] ?? null) ? substr($data['start_date'], 0, 10) : ($config['startsOn'] ?? now()->toDateString());

                            $data['frequency_config'] = $config;
                        } else {
                            $data['frequency'] = null;
                            $data['frequency_config'] = null;
                        }

                        unset($data['days_of_week']);

                        return $data;
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
