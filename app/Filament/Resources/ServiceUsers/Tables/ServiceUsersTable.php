<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceUsers\Tables;

use App\Enums\EngagementStatus;
use App\Enums\ServiceTeam;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
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
                TextColumn::make('first_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('last_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record): ?string => str_contains((string) $record->email, '@'.config('app.temp_email_domain', 'spinney.local')) ? 'Temporary email' : null)
                    ->color(fn ($record): ?string => str_contains((string) $record->email, '@'.config('app.temp_email_domain', 'spinney.local')) ? 'warning' : null),
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
                    ->query(function (Builder $query, array $data): void {
                        if ($data['value']) {
                            $query->whereHas('profile', fn ($q) => $q->where('target_service_team', $data['value']));
                        }
                    }),
                SelectFilter::make('engagement_status')
                    ->options(EngagementStatus::class)
                    ->query(function (Builder $query, array $data): void {
                        if ($data['value']) {
                            $query->whereHas('profile', fn ($q) => $q->where('engagement_status', $data['value']));
                        }
                    }),
                Filter::make('created_at')
                    ->label('Date Registered')
                    ->form([
                        DatePicker::make('registered_from')->label('Registered from'),
                        DatePicker::make('registered_until')->label('Registered until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['registered_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('people.created_at', '>=', $date))
                        ->when($data['registered_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('people.created_at', '<=', $date)))
                    ->indicateUsing(function (array $data): ?string {
                        if ($data['registered_from'] ?? null) {
                            return "Registered from {$data['registered_from']}";
                        }

                        return null;
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                RestoreAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
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
