<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyResource\RelationManagers;

use App\Filament\Resources\CompanyResource;
use App\Models\Company;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Named contacts at a company, stored as People of type `professional`.
 */
final class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    protected static ?string $modelLabel = 'contact';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-user';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('view', $ownerRecord) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('last_name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('job_title')
                    ->label('Role')
                    ->placeholder('e.g. Imam, Head teacher')
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(50),
                Toggle::make('consent_communications')
                    ->label('On mailing list')
                    ->default(true),
                CompanyResource::timestampToggle('email_invalid_at', 'Email invalid')
                    ->helperText('Tick when emails to this contact bounce.'),
                CompanyResource::timestampToggle('unsubscribed_at', 'Unsubscribed'),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('job_title')
                    ->label('Role')
                    ->placeholder('-'),
                TextColumn::make('email')
                    ->copyable()
                    ->placeholder('-'),
                TextColumn::make('phone')
                    ->placeholder('-'),
                IconColumn::make('consent_communications')
                    ->label('Mailing list')
                    ->boolean(),
                TextColumn::make('email_invalid_at')
                    ->label('Email invalid')
                    ->date()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('unsubscribed_at')
                    ->label('Unsubscribed')
                    ->date()
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->size(Size::Small)
                    ->mutateDataUsing(fn (array $data): array => [...$data, 'team_id' => $this->ownerCompany()->team_id]),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }

    private function ownerCompany(): Company
    {
        /** @var Company */
        return $this->getOwnerRecord();
    }
}
