<?php

declare(strict_types=1);

namespace App\Filament\Resources\Schedules\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class SchedulePeriodsRelationManager extends RelationManager
{
    protected static string $relationship = 'periods';

    protected static ?string $title = 'Time Slots';

    protected static ?string $recordTitleAttribute = 'date';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                DatePicker::make('date')
                    ->required()
                    ->native(false)
                    ->minDate(fn () => now()->toDateString()),
                TimePicker::make('start_time')
                    ->required()
                    ->native(false)
                    ->seconds(false),
                TimePicker::make('end_time')
                    ->required()
                    ->native(false)
                    ->seconds(false)
                    ->after('start_time'),
                Toggle::make('is_available')
                    ->default(true)
                    ->label('Available'),
                KeyValue::make('metadata')
                    ->keyLabel('Key')
                    ->valueLabel('Value'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('date')
            ->columns([
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('start_time')
                    ->sortable(),
                TextColumn::make('end_time')
                    ->sortable(),
                IconColumn::make('is_available')
                    ->boolean()
                    ->label('Available')
                    ->sortable(),
                TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->state(fn ($record): string => $record->duration_minutes.' min')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
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
