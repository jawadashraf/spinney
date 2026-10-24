<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\CreationSource;
use App\Filament\Exports\PeopleExporter;
use App\Filament\Resources\Donations\RelationManagers\DonationsRelationManager;
use App\Filament\Resources\PeopleResource\Pages\ListPeople;
use App\Filament\Resources\PeopleResource\Pages\ViewPeople;
use App\Filament\Resources\PeopleResource\RelationManagers\GiftAidDeclarationsRelationManager;
use App\Filament\Resources\PeopleResource\RelationManagers\NotesRelationManager;
use App\Filament\Resources\PeopleResource\RelationManagers\RelatedPeopleRelationManager;
use App\Filament\Resources\PeopleResource\RelationManagers\TasksRelationManager;
use App\Filament\Resources\PeopleResource\RelationManagers\ThirdPartyCarePlansRelationManager;
use App\Models\Company;
use App\Models\People;
use App\Models\User;
use App\Support\CustomFields;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

final class PeopleResource extends Resource
{
    protected static ?string $model = People::class;

    protected static ?string $modelLabel = 'person';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 2;

    protected static string|\UnitEnum|null $navigationGroup = 'Workspace';

    /**
     * @return array<NavigationItem>
     */
    public static function getNavigationItems(): array
    {
        return [
            NavigationItem::make(self::getNavigationLabel())
                ->group(self::getNavigationGroup())
                ->parentItem(self::getNavigationParentItem())
                ->icon(self::getNavigationIcon())
                ->activeIcon(self::getActiveNavigationIcon())
                ->isActiveWhen(fn (): bool => request()->routeIs('filament.app.resources.people.*') && request()->query('activeTab') !== 'donors')
                ->sort(self::getNavigationSort())
                ->badge(self::getNavigationBadge(), color: self::getNavigationBadgeColor())
                ->url(self::getUrl('index')),
        ];
    }

    /**
     * The count shown on the Donors menu item and tab, worked out once per request.
     */
    public static function getDonorsBadge(): ?string
    {
        return once(function (): ?string {
            $count = People::query()
                ->where('team_id', Filament::getTenant()?->getKey())
                ->where('is_service_user', false)
                ->whereHas('donations')
                ->count();

            return $count > 0 ? (string) $count : null;
        });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('first_name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(4)
                            ->disabled(fn (?People $record) => $record?->is_locked),
                        TextInput::make('last_name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(4)
                            ->disabled(fn (?People $record) => $record?->is_locked),
                        Select::make('company_id')
                            ->label('Organisation')
                            ->placeholder('None (Individual supporter / donor)')
                            ->relationship('company', 'name')
                            ->suffixAction(
                                Action::make('Create Company')
                                    ->model(Company::class)
                                    ->schema(fn (Schema $schema): Schema => $schema->components([
                                        TextInput::make('name')
                                            ->required(),
                                        Select::make('account_owner_id')
                                            ->model(Company::class)
                                            ->relationship('accountOwner', 'name')
                                            ->label('Account Owner')
                                            ->preload()
                                            ->searchable(),
                                        CustomFields::form()->forSchema($schema)->build()->columns(1),
                                    ]))
                                    ->modalWidth(Width::Large)
                                    ->slideOver()
                                    ->icon('heroicon-o-plus')
                                    ->action(function (array $data, Set $set): void {
                                        $company = Company::create($data);
                                        $set('company_id', $company->id);
                                    })
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->columnSpan(4)
                            ->disabled(fn (?People $record) => $record?->is_locked),
                        TextInput::make('email')
                            ->email()
                            ->maxLength(255)
                            ->columnSpan(4),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(50)
                            ->columnSpan(4),
                        Select::make('type')
                            ->label('Role')
                            ->options(fn (?People $record): array => [
                                'relative' => 'Relative',
                                'professional' => 'Professional',
                                ...($record?->type === 'donor' ? ['donor' => 'Donor (old tag)'] : []),
                            ])
                            ->helperText('Anyone who gives a donation is listed under Donors automatically.')
                            ->placeholder('General Person')
                            ->nullable()
                            ->columnSpan(4),
                        TextInput::make('postcode')
                            ->maxLength(20)
                            ->columnSpan(4),
                        Textarea::make('address')
                            ->rows(2)
                            ->columnSpan(8),
                    ])
                    ->columns(12),
                CustomFields::form()->forSchema($schema)->build()
                    ->columnSpanFull()
                    ->disabled(fn (?People $record) => $record?->is_locked),
                Toggle::make('is_locked')
                    ->label('Lock Profile')
                    ->helperText('When locked, service users cannot edit their details.')
                    ->visible(function () {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return $user?->hasAnyRole(['super_admin', 'admin']);
                    })
                    ->dehydrated(true)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')->label('')->imageSize(24)->circular(),
                TextColumn::make('first_name')
                    ->searchable(),
                TextColumn::make('last_name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('company.name')
                    ->label('Company')
                    ->placeholder('-')
                    ->url(fn (People $record): ?string => $record->company_id ? CompanyResource::getUrl('view', [$record->company_id]) : null)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->getStateUsing(fn (People $record): string => $record->created_by)
                    ->color(fn (People $record): string => $record->isSystemCreated() ? 'secondary' : 'primary'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('creation_source')
                    ->label('Creation Source')
                    ->options(CreationSource::class)
                    ->multiple(),
                TernaryFilter::make('is_service_user')
                    ->label('Include Service Users')
                    ->placeholder('General People Only')
                    ->trueLabel('Service Users Only')
                    ->falseLabel('General People Only')
                    ->queries(
                        true: fn (Builder $query) => $query->where('is_service_user', true),
                        false: fn (Builder $query) => $query->where('is_service_user', false),
                        blank: fn (Builder $query) => $query->where('is_service_user', false), // Hide by default
                    ),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('lock')
                        ->label('Lock Profile')
                        ->icon('heroicon-o-lock-closed')
                        ->color('danger')
                        ->visible(function (People $record): bool {
                            /** @var User|null $user */
                            $user = Auth::user();

                            return ! $record->is_locked && $user?->hasAnyRole(['super_admin', 'admin']);
                        })
                        ->action(fn (People $record) => $record->update(['is_locked' => true]))
                        ->requiresConfirmation(),
                    Action::make('unlock')
                        ->label('Unlock Profile')
                        ->icon('heroicon-o-lock-open')
                        ->color('success')
                        ->visible(function (People $record): bool {
                            /** @var User|null $user */
                            $user = Auth::user();

                            return $record->is_locked && $user?->hasAnyRole(['super_admin', 'admin']);
                        })
                        ->action(fn (People $record) => $record->update(['is_locked' => false]))
                        ->requiresConfirmation(),
                    RestoreAction::make(),
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(PeopleExporter::class),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TasksRelationManager::class,
            NotesRelationManager::class,
            RelatedPeopleRelationManager::class,
            ThirdPartyCarePlansRelationManager::class,
            DonationsRelationManager::class,
            GiftAidDeclarationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPeople::route('/'),
            'view' => ViewPeople::route('/{record}'),
        ];
    }

    /**
     * @return Builder<People>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var User|null $user */
        $user = Auth::user();

        /** @var Builder<People> $query */
        $query = parent::getEloquentQuery()
            ->where('is_service_user', false)
            ->when($user?->hasRole('service_user'), fn (Builder $query) => $query->where('user_id', Auth::id()))
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        return $query;
    }
}
