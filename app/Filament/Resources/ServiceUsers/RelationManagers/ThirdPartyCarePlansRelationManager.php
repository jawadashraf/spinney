<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceUsers\RelationManagers;

use App\Enums\ThirdPartyCarePlanStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class ThirdPartyCarePlansRelationManager extends RelationManager
{
    protected static string $relationship = 'thirdPartyCarePlans';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-building-office';

    public static function getTabComponent(Model $ownerRecord, string $pageClass): Tab
    {
        return Tab::make('External Care')
            ->badge($ownerRecord->thirdPartyCarePlans()->count())
            ->badgeColor('info')
            ->icon('heroicon-o-building-office');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Provider Information')
                    ->schema([
                        Select::make('provider_name')
                            ->options([
                                'Turning Point' => 'Turning Point',
                                'Addiction Dependency Services' => 'Addiction Dependency Services',
                                'Other' => 'Other',
                            ])
                            ->required()
                            ->live(),
                        TextInput::make('provider_name_other')
                            ->label('Provider Name')
                            ->required(fn (Get $get): bool => $get('provider_name') === 'Other')
                            ->visible(fn (Get $get): bool => $get('provider_name') === 'Other'),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('provider_contact.email')
                                    ->label('Email')
                                    ->email()
                                    ->nullable(),
                                TextInput::make('provider_contact.phone')
                                    ->label('Phone')
                                    ->tel()
                                    ->nullable(),
                                Textarea::make('provider_contact.address')
                                    ->label('Address')
                                    ->rows(2)
                                    ->nullable()
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->collapsible(),

                Section::make('Status & Dates')
                    ->schema([
                        Select::make('status')
                            ->options(ThirdPartyCarePlanStatus::class)
                            ->default(ThirdPartyCarePlanStatus::PENDING)
                            ->required()
                            ->live(),
                        DatePicker::make('referral_date')
                            ->required()
                            ->default(now())
                            ->maxDate(now()),
                        DatePicker::make('start_date')
                            ->visible(fn (Get $get): bool => in_array($get('status'), ['in_progress', 'completed']))
                            ->minDate(fn (Get $get) => $get('referral_date'))
                            ->nullable(),
                        DatePicker::make('end_date')
                            ->visible(fn (Get $get): bool => $get('status') === 'completed')
                            ->minDate(fn (Get $get) => $get('start_date'))
                            ->nullable(),
                        Select::make('managers')
                            ->relationship('managers', 'name')
                            ->multiple()
                            ->preload()
                            ->label('Assigned Managers')
                            ->nullable(),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->collapsible(),

                Section::make('Notes')
                    ->schema([
                        RichEditor::make('notes')
                            ->label('Notes')
                            ->nullable()
                            ->helperText('Visible to service user if shared')
                            ->extraInputAttributes(['style' => 'min-height: 400px;'])
                            ->columnSpanFull(),
                        RichEditor::make('internal_notes')
                            ->label('Internal Notes')
                            ->nullable()
                            ->helperText('Visible only to staff')
                            ->extraInputAttributes(['style' => 'min-height: 400px;'])
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->columnSpanFull()
                    ->collapsible(),

                Section::make('Attachments')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('attachments')
                            ->collection('attachments')
                            ->multiple()
                            ->reorderable()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('provider_name')
            ->columns([
                TextColumn::make('provider_name')
                    ->label('Provider')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (ThirdPartyCarePlanStatus $state): string => match ($state) {
                        ThirdPartyCarePlanStatus::PENDING => 'warning',
                        ThirdPartyCarePlanStatus::IN_PROGRESS => 'info',
                        ThirdPartyCarePlanStatus::COMPLETED => 'success',
                        ThirdPartyCarePlanStatus::CANCELLED => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('referral_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('managers.name')
                    ->label('Assigned Managers')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->badge()
                    ->toggleable(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->options(ThirdPartyCarePlanStatus::class)
                    ->label('Status'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['people_id'] = $this->getOwnerRecord()->id;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]));
    }
}
