<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceUsers\RelationManagers;

use App\Filament\Resources\Calls\Actions\RecordCallOutcomeAction;
use App\Filament\Resources\Calls\Actions\RescheduleCallAction;
use App\Filament\Resources\Calls\CallResource;
use App\Filament\Resources\Calls\Schemas\CallInfolist;
use App\Models\Call;
use App\Models\People;
use App\Notifications\CallAssignedNotification;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class CallsRelationManager extends RelationManager
{
    protected static string $relationship = 'calls';

    protected static ?string $title = 'Call history';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-phone';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('assigned_user_id')
                    ->label('Assigned liaison')
                    ->relationship('assignee', 'name', fn (Builder $query): Builder => CallResource::scopeAssignableUsers($query))
                    ->searchable()
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

    public function infolist(Schema $schema): Schema
    {
        return CallInfolist::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['assignee', 'plan']))
            ->defaultSort('due_at', 'desc')
            ->columns([
                TextColumn::make('due_at')
                    ->label('Due')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->color(fn (Call $record): ?string => $record->isOverdue() ? 'danger' : null),
                TextColumn::make('assignee.name')
                    ->label('Liaison')
                    ->placeholder('Unassigned'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('outcome')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('attempt_count')
                    ->label('Attempts')
                    ->alignCenter(),
                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(60)
                    ->tooltip(fn (Call $record): ?string => $record->notes)
                    ->placeholder('—'),
                TextColumn::make('action_required')
                    ->label('Action required')
                    ->limit(40)
                    ->placeholder('—'),
                TextColumn::make('next_follow_up_at')
                    ->label('Next follow-up')
                    ->dateTime('d M Y')
                    ->placeholder('—'),
                IconColumn::make('safeguarding_raised')
                    ->label('SG')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('gray'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Schedule call')
                    ->mutateFormDataUsing(fn (array $data): array => [
                        ...$data,
                        'team_id' => $this->serviceUser()->team_id,
                        'original_due_at' => $data['due_at'],
                        'call_plan_id' => $this->serviceUser()->callPlans()->where('is_active', true)->value('id'),
                    ])
                    ->after(function (Call $record): void {
                        if ($record->assignee !== null && $record->assigned_user_id !== auth()->id()) {
                            $record->assignee->notify(new CallAssignedNotification($record));
                        }
                    }),
            ])
            ->recordActions([
                RecordCallOutcomeAction::make(),
                RescheduleCallAction::make(),
                ViewAction::make(),
            ]);
    }

    private function serviceUser(): People
    {
        /** @var People $owner */
        $owner = $this->getOwnerRecord();

        return $owner;
    }
}
