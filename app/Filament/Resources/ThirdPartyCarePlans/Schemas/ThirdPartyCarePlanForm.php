<?php

declare(strict_types=1);

namespace App\Filament\Resources\ThirdPartyCarePlans\Schemas;

use App\Enums\ThirdPartyCarePlanStatus;
use App\Models\People;
use App\Support\CustomFields;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

use Filament\Support\Icons\Heroicon;

final class ThirdPartyCarePlanForm
{
    public static function configure(Schema $schema): Schema
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
                    ->collapsible()
                    ->icon(Heroicon::BuildingOffice),

                Section::make('Service User')
                    ->schema([
                        Select::make('people_id')
                            ->label('Service User')
                            ->relationship('serviceUser', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->disabled(fn (string $operation): bool => $operation !== 'create')
                            ->helperText(fn (string $operation): ?string => $operation !== 'create' ? 'The service user cannot be changed once the care plan is created.' : null)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => self::updateServiceUserDetails($set, $state))
                            ->getOptionLabelFromRecordUsing(fn (People $record) => $record->name),
                        TextInput::make('service_user_email')
                            ->label('Service User Email')
                            ->email()
                            ->nullable()
                            ->visible(fn (Get $get): bool => filled($get('people_id')))
                            ->columnSpan(1),
                        TextInput::make('service_user_phone')
                            ->label('Service User Phone')
                            ->tel()
                            ->nullable()
                            ->visible(fn (Get $get): bool => filled($get('people_id')))
                            ->columnSpan(1),
                    ])

                    ->columns(2)
                    ->columnSpanFull()
                    ->collapsible()
                    ->icon(Heroicon::User),

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
                            ->helperText('Managers who will manage this care plan')
                            ->nullable(),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->collapsible()
                    ->icon(Heroicon::Calendar),

                CustomFields::form()
                    ->forSchema($schema)
                    ->build()
                    ->columnSpanFull(),

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
                    ->collapsible()
                    ->icon(Heroicon::PencilSquare),

                Section::make('Quick Upload Attachments')
                    ->description('Quickly add files here. Use the Attachments tab for detailed categorization and tags.')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('attachments')
                            ->collection('attachments')
                            ->multiple()
                            ->reorderable()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
                    ->collapsible()
                    ->icon(Heroicon::PaperClip),
            ]);
    }

    public static function updateServiceUserDetails(Set $set, ?string $state): void
    {
        if (! $state) {
            $set('service_user_email', null);
            $set('service_user_phone', null);

            return;
        }

        $person = People::find($state);

        if ($person) {
            $set('service_user_email', $person->email);
            $set('service_user_phone', $person->phone);
        }
    }
}

