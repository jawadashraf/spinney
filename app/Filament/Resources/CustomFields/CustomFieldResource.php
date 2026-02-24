<?php

declare(strict_types=1);

namespace App\Filament\Resources\CustomFields;

use App\Enums\CustomFields\CustomFieldWidth;
use App\Filament\Resources\CustomFields\Pages\CreateCustomField;
use App\Filament\Resources\CustomFields\Pages\EditCustomField;
use App\Filament\Resources\CustomFields\Pages\ListCustomFields;
use App\Models\Company;
use App\Models\CustomField;
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

final class CustomFieldResource extends Resource
{
    protected static ?string $model = CustomField::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-vertical';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Custom Field';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->components([
                        Select::make('entity_type')
                            ->options([
                                Company::class => 'Company',
                                Opportunity::class => 'Opportunity',
                                People::class => 'People',
                                Task::class => 'Task',
                                Note::class => 'Note',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Select $component): ?\Filament\Schemas\Components\Component => $component->getContainer()->getComponent(fn ($c): bool => $c->getName() === 'custom_field_section_id')?->state(null)),

                        Select::make('custom_field_section_id')
                            ->label('Section')
                            ->options(function ($get) {
                                $entityType = $get('entity_type');
                                if (! $entityType) {
                                    return [];
                                }

                                return CustomFieldSection::query()
                                    ->where('entity_type', $entityType)
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->nullable(),

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
                                'text' => 'Text',
                                'textarea' => 'Textarea',
                                'richtext' => 'Rich Text',
                                'select' => 'Select (Dropdown)',
                                'boolean' => 'Boolean (Toggle)',
                                'date' => 'Date',
                                'datetime' => 'Date & Time',
                                'tags' => 'Tags',
                            ])
                            ->required()
                            ->live(),

                        Select::make('width')
                            ->options(collect(CustomFieldWidth::cases())->mapWithKeys(fn ($case): array => [$case->value => $case->value])->toArray())
                            ->default(CustomFieldWidth::_100->value),

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
                TextColumn::make('section.name')
                    ->label('Section')
                    ->placeholder('General')
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
            \App\Filament\Resources\CustomFields\RelationManagers\OptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomFields::route('/'),
            'create' => CreateCustomField::route('/create'),
            'edit' => EditCustomField::route('/{record}/edit'),
        ];
    }
}
