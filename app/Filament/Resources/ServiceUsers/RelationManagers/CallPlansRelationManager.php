<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceUsers\RelationManagers;

use App\Filament\Resources\CallPlans\Schemas\CallPlanForm;
use App\Models\CallPlan;
use App\Models\People;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class CallPlansRelationManager extends RelationManager
{
    protected static string $relationship = 'callPlans';

    protected static ?string $title = 'Call plan';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-calendar-days';

    public function form(Schema $schema): Schema
    {
        return CallPlanForm::configure($schema, forServiceUser: true);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['assignee', 'nextCall']))
            ->columns([
                TextColumn::make('frequency')
                    ->badge(),
                TextColumn::make('assignee.name')
                    ->label('Liaison')
                    ->placeholder('Unassigned'),
                TextColumn::make('nextCall.due_at')
                    ->label('Next call')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('None scheduled')
                    ->color(fn (CallPlan $record): ?string => $record->nextCall?->isOverdue() ? 'danger' : null),
                TextColumn::make('starts_on')
                    ->date('d M Y'),
                TextColumn::make('ends_on')
                    ->date('d M Y')
                    ->placeholder('—'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('New call plan')
                    ->visible(fn (): bool => ! $this->serviceUser()->callPlans()->where('is_active', true)->exists())
                    ->mutateFormDataUsing(fn (array $data): array => [
                        ...$data,
                        'team_id' => $this->serviceUser()->team_id,
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    private function serviceUser(): People
    {
        /** @var People $owner */
        $owner = $this->getOwnerRecord();

        return $owner;
    }
}
