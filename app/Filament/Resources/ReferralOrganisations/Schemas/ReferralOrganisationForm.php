<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReferralOrganisations\Schemas;

use App\Enums\ReferralPriority;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ReferralOrganisationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Organisation Overview')
                    ->compact()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Organisation / Service Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('area_of_support')
                            ->label('Area of Support / Category')
                            ->required()
                            ->maxLength(255)
                            ->datalist([
                                'Benefits support',
                                'Carers support',
                                'Debt / money advice',
                                'Domestic abuse',
                                'Emergency / immediate danger',
                                'Emergency financial help',
                                'Employment / training',
                                'Food / essentials',
                                'Food banks',
                                'Gambling harms',
                                'Homelessness',
                                'Hostels / supported accommodation',
                                'Housing support',
                                'Legal support',
                                'Mental health — emotional support',
                                'Mental health — therapy',
                                'Mental health — urgent',
                                'Refugees / asylum',
                                'Safeguarding — adults',
                                'Safeguarding — children',
                                'Sexual violence',
                                'Substance misuse',
                                'Zakat support',
                            ])
                            ->columnSpan(1),

                        Select::make('priority')
                            ->options(ReferralPriority::class)
                            ->default(ReferralPriority::Routine)
                            ->required()
                            ->columnSpan(1),

                        Toggle::make('is_active')
                            ->label('Active in directory')
                            ->default(true)
                            ->columnSpan(1),

                        Textarea::make('what_they_help_with')
                            ->label('What they help with')
                            ->required()
                            ->rows(3)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ]),

                Section::make('Contact Details')
                    ->compact()
                    ->columns(2)
                    ->schema([
                        TextInput::make('telephone')
                            ->label('Telephone / Helpline')
                            ->tel()
                            ->nullable()
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('email_or_text')
                            ->label('Email / SMS instructions')
                            ->nullable()
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('website_url')
                            ->label('Official Website / Referral Source URL')
                            ->url()
                            ->nullable()
                            ->maxLength(2048)
                            ->columnSpanFull(),

                        Textarea::make('address')
                            ->label('Area / Physical Address')
                            ->rows(2)
                            ->nullable()
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),

                Section::make('Referral Procedure & Volunteer Advice')
                    ->compact()
                    ->columns(1)
                    ->schema([
                        Textarea::make('how_to_refer')
                            ->label('How to access / refer (Pathways, hours, vouchers)')
                            ->rows(3)
                            ->nullable()
                            ->maxLength(2000),

                        Textarea::make('volunteer_notes')
                            ->label('Volunteer referral notes (Crucial cautions & reminders)')
                            ->rows(4)
                            ->nullable()
                            ->maxLength(3000)
                            ->helperText('Include specific volunteer guidance such as "Do not promise a bed" or "Ask if safe to speak".'),

                        TextInput::make('last_checked_at')
                            ->label('Last Verified Date / Note')
                            ->nullable()
                            ->maxLength(50),
                    ]),
            ]);
    }
}
