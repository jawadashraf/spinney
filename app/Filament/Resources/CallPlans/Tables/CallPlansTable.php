<?php

declare(strict_types=1);

namespace App\Filament\Resources\CallPlans\Tables;

use App\Enums\CallFrequency;
use App\Enums\CallStatus;
use App\Filament\Resources\Calls\CallResource;
use App\Filament\Resources\ServiceUsers\ServiceUserResource;
use App\Models\CallPlan;
use App\Models\User;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class CallPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['serviceUser', 'assignee', 'nextCall'])
                ->withMax('calls', 'completed_at'))
            ->columns([
                TextColumn::make('serviceUser.name')
                    ->label('Service user')
                    ->searchable()
                    ->weight('medium')
                    ->url(fn (CallPlan $record): string => ServiceUserResource::getUrl('edit', ['record' => $record->people_id])),
                TextColumn::make('assignee.name')
                    ->label('Liaison')
                    ->badge()
                    ->placeholder('Unassigned'),
                TextColumn::make('frequency')
                    ->badge()
                    ->description(fn (CallPlan $record): ?string => $record->frequency === CallFrequency::Custom
                        ? "every {$record->interval_days} days"
                        : null),
                TextColumn::make('nextCall.due_at')
                    ->label('Next call')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('None scheduled')
                    ->color(fn (CallPlan $record): ?string => $record->nextCall?->isOverdue() ? 'danger' : null),
                TextColumn::make('calls_max_completed_at')
                    ->label('Last contact')
                    ->dateTime('d M Y')
                    ->placeholder('Never')
                    ->sortable(),
                TextColumn::make('ends_on')
                    ->date('d M Y')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('assigned_user_id')
                    ->label('Liaison')
                    ->relationship('assignee', 'name', fn (Builder $query): Builder => CallResource::scopeAssignableUsers($query))
                    ->preload(),
                SelectFilter::make('frequency')
                    ->options(CallFrequency::class),
                TernaryFilter::make('is_active')
                    ->label('Active')
                    ->default(true),
                Filter::make('withoutOpenCall')
                    ->label('No call booked')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave(
                        'calls',
                        fn (Builder $calls): Builder => $calls->where('calls.status', CallStatus::Scheduled),
                    )),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('reassignPlans')
                        ->label('Assign liaison')
                        ->icon(Heroicon::OutlinedUserPlus)
                        ->authorizeIndividualRecords('update')
                        ->schema([
                            Select::make('assigned_user_id')
                                ->label('Liaison')
                                ->options(fn (): array => CallResource::assignableUserOptions())
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $liaison = User::query()->findOrFail((int) $data['assigned_user_id']);

                            /** @var CallPlan $plan */
                            foreach ($records as $plan) {
                                $plan->update(['assigned_user_id' => $liaison->id]);
                            }

                            Notification::make()
                                ->title($records->count().' '.str('plan')->plural($records->count())." assigned to {$liaison->name}")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon(Heroicon::OutlinedPauseCircle)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalDescription('Open calls for these plans will be cancelled.')
                        ->authorizeIndividualRecords('update')
                        ->action(function (Collection $records): void {
                            /** @var CallPlan $plan */
                            foreach ($records as $plan) {
                                $plan->update(['is_active' => false]);
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
