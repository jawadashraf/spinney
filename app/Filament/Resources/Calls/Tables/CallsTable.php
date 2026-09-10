<?php

declare(strict_types=1);

namespace App\Filament\Resources\Calls\Tables;

use App\Enums\CallOutcome;
use App\Enums\CallStatus;
use App\Filament\Resources\Calls\Actions\CancelCallAction;
use App\Filament\Resources\Calls\Actions\ReassignCallAction;
use App\Filament\Resources\Calls\Actions\ReassignCallsBulkAction;
use App\Filament\Resources\Calls\Actions\RecordCallOutcomeAction;
use App\Filament\Resources\Calls\Actions\RescheduleCallAction;
use App\Filament\Resources\Calls\Actions\RescheduleCallsBulkAction;
use App\Filament\Resources\Calls\CallResource;
use App\Filament\Resources\ServiceUsers\ServiceUserResource;
use App\Models\Call;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class CallsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['serviceUser', 'assignee', 'plan']))
            ->defaultSort('due_at')
            ->columns([
                TextColumn::make('serviceUser.name')
                    ->label('Service user')
                    ->searchable()
                    ->weight('medium')
                    ->url(fn (Call $record): string => ServiceUserResource::getUrl('edit', ['record' => $record->people_id])),
                TextColumn::make('serviceUser.phone')
                    ->label('Phone')
                    ->placeholder('—')
                    ->url(fn (Call $record): ?string => filled($record->serviceUser?->phone) ? 'tel:'.$record->serviceUser->phone : null)
                    ->toggleable(),
                TextColumn::make('due_at')
                    ->label('Due')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->color(fn (Call $record): ?string => $record->isOverdue() ? 'danger' : null)
                    ->description(fn (Call $record): ?string => $record->isOverdue() ? 'Overdue '.$record->due_at->diffForHumans() : null),
                TextColumn::make('assignee.name')
                    ->label('Liaison')
                    ->badge()
                    ->color('primary')
                    ->placeholder('Unassigned'),
                TextColumn::make('plan.frequency')
                    ->label('Frequency')
                    ->badge()
                    ->placeholder('Ad-hoc'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('attempt_count')
                    ->label('Attempts')
                    ->numeric(0)
                    ->alignCenter(),
                TextColumn::make('outcome')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('action_required')
                    ->label('Action required')
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('next_follow_up_at')
                    ->label('Next follow-up')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('completed_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('safeguarding_raised')
                    ->label('SG')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('gray')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('assigned_user_id')
                    ->label('Liaison')
                    ->relationship('assignee', 'name', fn (Builder $query): Builder => CallResource::scopeAssignableUsers($query))
                    ->multiple()
                    ->preload(),
                SelectFilter::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name')
                    ->preload()
                    ->visible(fn (): bool => CallResource::isManager()),
                SelectFilter::make('status')
                    ->options(CallStatus::class)
                    ->multiple(),
                SelectFilter::make('outcome')
                    ->options(CallOutcome::class),
                Filter::make('due_between')
                    ->schema([
                        DatePicker::make('due_from')->label('Due from'),
                        DatePicker::make('due_until')->label('Due until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['due_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('due_at', '>=', $date))
                        ->when($data['due_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('due_at', '<=', $date))),
                TernaryFilter::make('safeguarding_raised')
                    ->label('Concern raised'),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                RecordCallOutcomeAction::make(),
                ActionGroup::make([
                    ViewAction::make(),
                    RescheduleCallAction::make(),
                    ReassignCallAction::make(),
                    EditAction::make(),
                    CancelCallAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ReassignCallsBulkAction::make(),
                    RescheduleCallsBulkAction::make(),
                ]),
            ]);
    }
}
