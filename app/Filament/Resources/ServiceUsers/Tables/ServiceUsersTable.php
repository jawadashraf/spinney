<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceUsers\Tables;

use App\Enums\EngagementStatus;
use App\Enums\ServiceTeam;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ServiceUsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('')
                    ->circular(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('profile.target_service_team')
                    ->label('Service Team')
                    ->badge()
                    ->sortable(),
                TextColumn::make('profile.engagement_status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('profile.treatment_outcome')
                    ->label('Outcome')
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('target_service_team')
                    ->options(ServiceTeam::class)
                    ->query(function (Builder $query, array $data) {
                        if ($data['value']) {
                            $query->whereHas('profile', fn ($q) => $q->where('target_service_team', $data['value']));
                        }
                    }),
                SelectFilter::make('engagement_status')
                    ->options(EngagementStatus::class)
                    ->query(function (Builder $query, array $data) {
                        if ($data['value']) {
                            $query->whereHas('profile', fn ($q) => $q->where('engagement_status', $data['value']));
                        }
                    }),
                TrashedFilter::make(),
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
