<?php

declare(strict_types=1);

namespace App\Filament\Resources\PeopleResource\RelationManagers;

use App\Enums\ThirdPartyCarePlanStatus;
use App\Filament\Resources\ThirdPartyCarePlans\ThirdPartyCarePlanResource;
use App\Models\ThirdPartyCarePlan;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ThirdPartyCarePlansRelationManager extends RelationManager
{
    protected static string $relationship = 'thirdPartyCarePlans';

    protected static ?string $title = 'External Care Plans';

    protected static ?string $recordTitleAttribute = 'provider_name';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('provider_name')
                    ->label('Provider')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (ThirdPartyCarePlanStatus $state): string => match ($state) {
                        ThirdPartyCarePlanStatus::PENDING => 'warning',
                        ThirdPartyCarePlanStatus::IN_PROGRESS => 'info',
                        ThirdPartyCarePlanStatus::COMPLETED => 'success',
                        ThirdPartyCarePlanStatus::CANCELLED => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('referral_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable()
                    ->placeholder('Not started'),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->placeholder('Not completed'),
                TextColumn::make('managers.name')
                    ->label('Assigned Managers')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ThirdPartyCarePlanStatus::class)
                    ->label('Status'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record) => ThirdPartyCarePlanResource::getUrl('view', ['record' => $record])),
                EditAction::make()
                    ->url(fn ($record) => ThirdPartyCarePlanResource::getUrl('edit', ['record' => $record]))
                    ->visible(fn () => auth()->user()->can('update', $this->ownerRecord)),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->url(fn () => ThirdPartyCarePlanResource::getUrl('create', ['people_id' => $this->ownerRecord->id]))
                    ->visible(fn () => auth()->user()->can('create', ThirdPartyCarePlan::class)),
            ]);
    }
}
