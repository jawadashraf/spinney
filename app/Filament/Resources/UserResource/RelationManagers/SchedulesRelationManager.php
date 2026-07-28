<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\RelationManagers;

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
                                'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
                            ];
                        }

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
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
