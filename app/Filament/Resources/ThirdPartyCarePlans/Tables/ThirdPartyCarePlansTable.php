<?php

declare(strict_types=1);

namespace App\Filament\Resources\ThirdPartyCarePlans\Tables;

use App\Enums\ThirdPartyCarePlanStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

final class ThirdPartyCarePlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('serviceUser.name')
                    ->label('Service User')
                    ->searchable()
                    ->sortable(),
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
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('managers.name')
                    ->label('Assigned Managers')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->badge()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->options(ThirdPartyCarePlanStatus::class)
                    ->label('Status'),
                SelectFilter::make('service_user')
                    ->relationship('serviceUser', 'name')
                    ->label('Service User')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('manager')
                    ->relationship('managers', 'name')
                    ->label('Assigned Manager')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
