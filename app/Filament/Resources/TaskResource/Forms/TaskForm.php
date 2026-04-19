<?php

declare(strict_types=1);

namespace App\Filament\Resources\TaskResource\Forms;

use App\Enums\TaskType;
use App\Support\CustomFields;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

final class TaskForm
{
    /**
     * @param  array<string>  $excludeFields
     *
     * @throws \Exception
     */
    public static function get(Schema $schema, array $excludeFields = []): Schema
    {
        $user = auth()->user();
        $isAdminOrManager = $user->hasAnyRole(['super_admin', 'admin', 'manager']);

        $components = [
            Select::make('type')
                ->options(TaskType::class)
                ->default(TaskType::GeneralTask->value)
                ->required()
                ->live()
                ->columnSpan(1),

            Select::make('department_id')
                ->label('Department')
                ->relationship('department', 'name')
                ->searchable()
                ->preload()
                ->visible($isAdminOrManager)
                ->default(fn (): ?int => $user->departments()->first()?->id)
                ->columnSpan(1),

            TextInput::make('title')
                ->required()
                ->columnSpanFull(),

            DateTimePicker::make('due_date')
                ->label('Due Date')
                ->nullable()
                ->columnSpan(1),
        ];

        if (! in_array('companies', $excludeFields)) {
            $components[] = Select::make('companies')
                ->label('Companies')
                ->multiple()
                ->relationship('companies', 'name')
                ->columnSpanFull();
        }

        if (! in_array('people', $excludeFields)) {
            $components[] = Select::make('people')
                ->label('People')
                ->multiple()
                ->relationship('people', 'name')
                ->required(fn (Get $get): bool => $get('type') === TaskType::FollowUpCall->value);
        }

        $components[] = Select::make('assignees')
            ->label('Assignees')
            ->multiple()
            ->relationship('assignees', 'name')
            ->visible($isAdminOrManager)
            ->nullable();

        $components[] = CustomFields::form()->forSchema($schema)->except($excludeFields)->build()->columnSpanFull();

        return $schema
            ->components($components)
            ->columns(2);
    }
}
