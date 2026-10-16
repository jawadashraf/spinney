<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\CompanyStatus;
use App\Enums\CompanyVerificationStatus;
use App\Filament\Exports\CompanyExporter;
use App\Filament\Resources\CompanyResource\Pages\ListCompanies;
use App\Filament\Resources\CompanyResource\Pages\ViewCompany;
use App\Filament\Resources\OutreachEmails\OutreachEmailResource;
use App\Models\Company;
use App\Models\CompanyType;
use App\Models\MailingGroup;
use App\Models\OutreachEmail;
use App\Support\CustomFields;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
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
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

final class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home-modern';

    protected static ?int $navigationSort = 2;

    protected static string|\UnitEnum|null $navigationGroup = 'Workspace';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Company')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Select::make('company_type_id')
                            ->label('Type')
                            ->relationship(
                                'companyType',
                                'name',
                                fn (Builder $query): Builder => $query->where('team_id', Filament::getTenant()?->getKey()),
                            )
                            ->preload()
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionUsing(fn (array $data): int => CompanyType::query()->create([...$data, 'team_id' => Filament::getTenant()?->getKey()])->getKey()),
                        Select::make('status')
                            ->options(CompanyStatus::class)
                            ->default(CompanyStatus::Active)
                            ->required()
                            ->helperText('Inactive companies are kept but never emailed.'),
                        Select::make('mailingGroups')
                            ->label('Mailing groups')
                            ->relationship(
                                'mailingGroups',
                                'name',
                                fn (Builder $query): Builder => $query->where('team_id', Filament::getTenant()?->getKey()),
                            )
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionUsing(fn (array $data): int => MailingGroup::query()->create([...$data, 'team_id' => Filament::getTenant()?->getKey()])->getKey()),
                        Select::make('account_owner_id')
                            ->relationship('accountOwner', 'name')
                            ->label('Staff lead')
                            ->nullable()
                            ->preload()
                            ->searchable(),
                        Select::make('parent_company_id')
                            ->label('Part of')
                            ->relationship(
                                'parentCompany',
                                'name',
                                fn (Builder $query, ?Company $record): Builder => $query
                                    ->where('team_id', Filament::getTenant()?->getKey())
                                    ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey())),
                            )
                            ->searchable()
                            ->helperText('The organisation that runs this one, such as the masjid behind a madrasah.'),
                    ]),
                Section::make('Contact details')
                    ->schema([
                        TextInput::make('email')
                            ->label('General email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(50),
                        TextInput::make('website')
                            ->url()
                            ->maxLength(255),
                        Textarea::make('address')
                            ->rows(3),
                        TextInput::make('postcode')
                            ->maxLength(20),
                        self::timestampToggle('email_invalid_at', 'Email invalid')
                            ->helperText('Tick when emails to the general address bounce. It will be skipped when sending.'),
                        self::timestampToggle('unsubscribed_at', 'Unsubscribed')
                            ->helperText('The general address has asked not to receive emails.'),
                    ]),

                Section::make('Verification')
                    ->schema([
                        Select::make('verification_status')
                            ->label('Status')
                            ->options(CompanyVerificationStatus::class)
                            ->default(CompanyVerificationStatus::Unverified)
                            ->required()
                            ->helperText('Mark Verified once staff have confirmed the details with the organisation.'),
                        Textarea::make('verification_note')
                            ->label('Note')
                            ->rows(3),
                        Textarea::make('source')
                            ->label('Where the details came from')
                            ->rows(2)
                            ->helperText('For example a website, directory or "phoned the office".'),
                    ])
                    ->collapsible(),

                CustomFields::form()->forSchema($schema)->build()->columns(1),
            ])
            ->columns(1)
            ->inlineLabel();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')->label('')->imageSize(28)->square(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('companyType.name')
                    ->label('Type')
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('email')
                    ->label('General email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('mailingGroups.name')
                    ->label('Mailing groups')
                    ->badge()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->toggleable(),
                TextColumn::make('verification_status')
                    ->label('Verification')
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('parentCompany.name')
                    ->label('Part of')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('contacts_count')
                    ->label('Contacts')
                    ->counts('contacts')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('accountOwner.name')
                    ->label('Staff lead')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Last Update')
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('company_type_id')
                    ->label('Type')
                    ->relationship(
                        'companyType',
                        'name',
                        fn (Builder $query): Builder => $query->where('team_id', Filament::getTenant()?->getKey()),
                    )
                    ->multiple()
                    ->preload(),
                SelectFilter::make('mailingGroups')
                    ->label('Mailing group')
                    ->relationship(
                        'mailingGroups',
                        'name',
                        fn (Builder $query): Builder => $query->where('team_id', Filament::getTenant()?->getKey()),
                    )
                    ->multiple()
                    ->preload(),
                SelectFilter::make('status')
                    ->options(CompanyStatus::class),
                SelectFilter::make('verification_status')
                    ->label('Verification')
                    ->options(CompanyVerificationStatus::class),
                Filter::make('no_email')
                    ->label('No email yet')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNull('email')
                        ->whereDoesntHave('contacts', fn (Builder $query) => $query->whereNotNull('email'))),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    RestoreAction::make(),
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('email')
                        ->label('Email selected')
                        ->icon('heroicon-o-envelope')
                        ->authorize(fn (): bool => auth()->user()?->can('create', OutreachEmail::class) ?? false)
                        ->deselectRecordsAfterCompletion()
                        ->action(fn (Collection $records) => redirect(OutreachEmailResource::getUrl('create', [
                            'companies' => $records->modelKeys() === [] ? null : implode(',', $records->modelKeys()),
                        ]))),
                    ExportBulkAction::make()
                        ->exporter(CompanyExporter::class),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanies::route('/'),
            'view' => ViewCompany::route('/{record}'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    /**
     * @return Builder<Company>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<Company> $query */
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        return $query;
    }

    /**
     * A toggle that stores the time it was switched on, and clears it when switched off.
     */
    public static function timestampToggle(string $column, string $label): Toggle
    {
        return Toggle::make($column)
            ->label($label)
            ->formatStateUsing(fn (mixed $state): bool => filled($state))
            ->dehydrateStateUsing(fn (bool $state, ?Model $record): ?Carbon => $state ? ($record?->getAttribute($column) ?? now()) : null);
    }
}
