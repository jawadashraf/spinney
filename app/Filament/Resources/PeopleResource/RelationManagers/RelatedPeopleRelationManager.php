<?php

declare(strict_types=1);

namespace App\Filament\Resources\PeopleResource\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class RelatedPeopleRelationManager extends RelationManager
{
    protected static string $relationship = 'relatedPeople';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-link';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('relation_type')
                    ->required()
                    ->maxLength(255),
                Checkbox::make('is_emergency_contact'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('relation_type'),
                IconColumn::make('is_emergency_contact')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        TextInput::make('relation_type')->required(),
                        Checkbox::make('is_emergency_contact'),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }
}
