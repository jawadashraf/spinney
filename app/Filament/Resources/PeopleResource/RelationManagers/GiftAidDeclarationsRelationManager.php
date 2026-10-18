<?php

declare(strict_types=1);

namespace App\Filament\Resources\PeopleResource\RelationManagers;

use App\Enums\GiftAidDeclarationMethod;
use App\Models\GiftAidDeclaration;
use App\Models\People;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * A person's Gift Aid declarations (AC DN-002), shown only to managers and admins.
 */
final class GiftAidDeclarationsRelationManager extends RelationManager
{
    protected static string $relationship = 'giftAidDeclarations';

    protected static ?string $title = 'Gift Aid';

    protected static ?string $modelLabel = 'Gift Aid declaration';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-document-check';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('viewAny', GiftAidDeclaration::class) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('declared_on')
                    ->label('Declaration date')
                    ->default(today())
                    ->maxDate(today())
                    ->required(),
                Select::make('method')
                    ->label('How it was made')
                    ->options(GiftAidDeclarationMethod::class)
                    ->required(),
                Toggle::make('covers_past_donations')
                    ->label('Covers gifts from the past 4 years')
                    ->default(true),
                Toggle::make('covers_future_donations')
                    ->label('Covers future gifts')
                    ->default(true),
                Textarea::make('address')
                    ->label('Home address')
                    ->helperText('As given on the declaration. HMRC needs at least the house name or number.')
                    ->default(fn (): ?string => $this->ownerPerson()->address)
                    ->required()
                    ->rows(2),
                TextInput::make('postcode')
                    ->default(fn (): ?string => $this->ownerPerson()->postcode)
                    ->maxLength(20),
                Textarea::make('notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('declared_on')
            ->columns([
                TextColumn::make('declared_on')
                    ->label('Declared')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->state(fn (GiftAidDeclaration $record): string => $record->isWithdrawn() ? 'Withdrawn' : 'Active')
                    ->color(fn (string $state): string => $state === 'Active' ? 'success' : 'gray')
                    ->description(fn (GiftAidDeclaration $record): ?string => $record->withdrawn_on?->toFormattedDateString()),
                TextColumn::make('coverage')
                    ->label('Covers')
                    ->state(fn (GiftAidDeclaration $record): string => match (true) {
                        $record->covers_past_donations && $record->covers_future_donations => 'Past 4 years and future gifts',
                        $record->covers_future_donations => 'Future gifts',
                        $record->covers_past_donations => 'Past 4 years',
                        default => 'Gifts on the declaration date',
                    }),
                TextColumn::make('method')
                    ->label('How'),
                TextColumn::make('postcode')
                    ->placeholder('-'),
                TextColumn::make('donations_count')
                    ->label('Donations')
                    ->counts('donations'),
            ])
            ->defaultSort('declared_on', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Add declaration')
                    ->icon('heroicon-o-plus')
                    ->size(Size::Small)
                    ->mutateDataUsing(fn (array $data): array => [...$data, 'team_id' => $this->ownerPerson()->team_id]),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('withdraw')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (GiftAidDeclaration $record): bool => $record->withdrawn_on === null && (auth()->user()?->can('update', $record) ?? false))
                        ->modalDescription('Gifts given on or after this date will no longer be Gift Aid under this declaration. Gifts already claimed are not changed.')
                        ->schema([
                            DatePicker::make('withdrawn_on')
                                ->label('Withdrawn on')
                                ->default(today())
                                ->minDate(fn (GiftAidDeclaration $record) => $record->declared_on)
                                ->required(),
                        ])
                        ->action(function (GiftAidDeclaration $record, array $data): void {
                            $record->update(['withdrawn_on' => $data['withdrawn_on']]);

                            Notification::make()->title('Declaration withdrawn')->success()->send();
                        }),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }

    private function ownerPerson(): People
    {
        /** @var People */
        return $this->getOwnerRecord();
    }
}
