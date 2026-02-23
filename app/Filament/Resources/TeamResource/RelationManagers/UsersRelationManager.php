<?php

declare(strict_types=1);

namespace App\Filament\Resources\TeamResource\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

final class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                AttachAction::make()
                    ->recordSelectOptionsQuery(fn ($query) => $query->whereDoesntHave('roles', fn ($query) => $query->where('name', 'service_user')))
                    ->recordSelect(fn (Select $select) => $select->rules([
                        'required',
                        fn () => function (string $attribute, $value, \Closure $fail) {
                            if (! \App\Models\User::query()
                                ->where('id', $value)
                                ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'service_user'))
                                ->exists()) {
                                $fail('The selected member is invalid.');
                            }
                        },
                    ])),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
