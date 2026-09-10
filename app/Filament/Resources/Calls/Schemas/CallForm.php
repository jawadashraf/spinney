<?php

declare(strict_types=1);

namespace App\Filament\Resources\Calls\Schemas;

use App\Filament\Resources\Calls\CallResource;
use App\Models\CallPlan;
use App\Models\Department;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

final class CallForm
{
    public static function configure(Schema $schema): Schema
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
                    ->live()
                    ->afterStateUpdated(fn (Set $set): mixed => $set('call_plan_id', null))
                    ->disabledOn('edit'),
                Select::make('call_plan_id')
                    ->label('Call plan')
                    ->options(fn (Get $get): array => CallPlan::query()
                        ->where('people_id', $get('people_id'))
                        ->where('is_active', true)
                        ->get()
                        ->mapWithKeys(fn (CallPlan $plan): array => [$plan->id => $plan->frequency->getLabel()])
                        ->all())
                    ->placeholder('Ad-hoc call')
                    ->visible(fn (Get $get): bool => filled($get('people_id'))),
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
                DateTimePicker::make('due_at')
                    ->label('Due')
                    ->seconds(false)
                    ->required(),
                Textarea::make('reason')
                    ->label('Reason for call')
                    ->rows(3)
                    ->maxLength(2000)
                    ->columnSpanFull(),
            ]);
    }
}
