<?php

declare(strict_types=1);

namespace App\Filament\Resources\CallPlans\Schemas;

use App\Enums\CallFrequency;
use App\Filament\Resources\Calls\CallResource;
use App\Models\Department;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

final class CallPlanForm
{
    public static function configure(Schema $schema, bool $forServiceUser = false): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('people_id')
                    ->label('Service user')
                    ->relationship('serviceUser', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->scopedUnique(modifyQueryUsing: fn (Builder $query): Builder => $query->where('is_active', true))
                    ->validationMessages(['unique' => 'This service user already has an active call plan.'])
                    ->disabledOn('edit')
                    ->hidden($forServiceUser)
                    ->columnSpanFull(),
                Select::make('assigned_user_id')
                    ->label('Assigned liaison')
                    ->relationship('assignee', 'name', fn (Builder $query): Builder => CallResource::scopeAssignableUsers($query))
                    ->searchable()
                    ->preload(),
                Select::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name')
                    ->default(fn (): ?int => Department::query()->where('name', 'Liaison')->value('id'))
                    ->preload(),
                Select::make('frequency')
                    ->label('Call frequency')
                    ->options(CallFrequency::class)
                    ->default(CallFrequency::Weekly->value)
                    ->required()
                    ->live(),
                TextInput::make('interval_days')
                    ->label('Every')
                    ->integer()
                    ->minValue(1)
                    ->maxValue(365)
                    ->suffix('days')
                    ->required()
                    ->visible(fn (Get $get): bool => self::frequencyValue($get) === CallFrequency::Custom->value),
                DatePicker::make('starts_on')
                    ->label('First call on')
                    ->default(today())
                    ->required(),
                DatePicker::make('ends_on')
                    ->label('Stop calling after')
                    ->afterOrEqual('starts_on'),
                TimePicker::make('preferred_time')
                    ->label('Preferred call time')
                    ->seconds(false),
                TextInput::make('max_attempts')
                    ->label('Attempts before marking missed')
                    ->integer()
                    ->minValue(1)
                    ->maxValue(10)
                    ->default(3)
                    ->required(),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->inline(false),
                Textarea::make('purpose')
                    ->label('Purpose of calls')
                    ->rows(3)
                    ->maxLength(2000)
                    ->columnSpanFull(),
            ]);
    }

    private static function frequencyValue(Get $get): ?string
    {
        $frequency = $get('frequency');

        return $frequency instanceof CallFrequency ? $frequency->value : $frequency;
    }
}
