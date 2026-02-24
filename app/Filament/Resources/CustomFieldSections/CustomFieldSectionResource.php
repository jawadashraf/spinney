<?php

declare(strict_types=1);

namespace App\Filament\Resources\CustomFieldSections;

use App\Filament\Resources\CustomFieldSections\Pages\CreateCustomFieldSection;
use App\Filament\Resources\CustomFieldSections\Pages\EditCustomFieldSection;
use App\Filament\Resources\CustomFieldSections\Pages\ListCustomFieldSections;
use App\Models\Company;
use App\Models\CustomFieldSection;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

final class CustomFieldSectionResource extends Resource
{
    protected static ?string $model = CustomFieldSection::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Field Section';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(1)
                    ->components([
                        Select::make('entity_type')
                            ->options([
                                Company::class => 'Company',
                                Opportunity::class => 'Opportunity',
                                People::class => 'People',
                                Task::class => 'Task',
                                Note::class => 'Note',
                            ])
                            ->required(),

                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('code')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule, $get) => $rule->where('entity_type', $get('entity_type'))
                                ->where('team_id', Auth::user()?->current_team_id)),

                        Select::make('type')
                            ->options([
                                'section' => 'Standard Section',
                                'headless' => 'Headless (No Title)',
                            ])
                            ->required()
                            ->default('section'),

                        TextInput::make('description')
                            ->maxLength(255),

                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),

                        Toggle::make('active')
                            ->default(true),

                        Toggle::make('system_defined')
                            ->disabled()
                            ->dehydrated(false)
                            ->label('System Defined'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('entity_type')
                    ->label('Entity')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                ToggleColumn::make('active'),
                TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('entity_type')
                    ->options([
                        Company::class => 'Company',
                        Opportunity::class => 'Opportunity',
                        People::class => 'People',
                        Task::class => 'Task',
                        Note::class => 'Note',
                    ]),
            ])
            ->defaultSort('entity_type');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomFieldSections::route('/'),
            'create' => CreateCustomFieldSection::route('/create'),
            'edit' => EditCustomFieldSection::route('/{record}/edit'),
        ];
    }
}
