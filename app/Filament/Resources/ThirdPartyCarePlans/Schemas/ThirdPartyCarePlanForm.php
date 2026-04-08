<?php

declare(strict_types=1);

namespace App\Filament\Resources\ThirdPartyCarePlans\Schemas;

use App\Enums\ThirdPartyCarePlanStatus;
use App\Models\People;
use App\Support\CustomFields;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
                            ->live()
                            ->reactive(),
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
                            ->reactive()
                            ->getOptionLabelFromRecordUsing(fn (People $record) => $record->name),
                        TextEntry::make('service_user_details')
                            ->label('Service User Contact Details')
                            ->visible(fn (Get $get): bool => filled($get('people_id')))
                            ->state(function (Get $get) {
                                $person = People::find($get('people_id'));
                                if (! $person) {
                                    return '-';
                                }

                                return "Email: {$person->email} | Phone: {$person->phone}";
                            }),
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
                            ->live()
                            ->reactive(),
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
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->nullable()
                            ->helperText('Visible to service user if shared')
                            ->columnSpanFull(),
                        Textarea::make('internal_notes')
                            ->label('Internal Notes')
                            ->rows(3)
                            ->nullable()
                            ->helperText('Visible only to staff')
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->columnSpanFull()
                    ->collapsible()
                    ->icon(Heroicon::PencilSquare),
            ]);
    }
}
