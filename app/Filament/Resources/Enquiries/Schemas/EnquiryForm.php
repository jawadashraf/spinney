<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enquiries\Schemas;

use App\Enums\EnquiryCategory;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class EnquiryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Caller Identification')
                    ->schema([
                        Select::make('people_id')
                            ->relationship('people', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required(),
                            ])
                            ->label('Caller'),
                    ]),

                Section::make('Enquiry Details')
                    ->schema([
                        Select::make('category')
                            ->options(EnquiryCategory::class)
                            ->native(false)
                            ->required(),

                        DateTimePicker::make('occurred_at')
                            ->default(now())
                            ->required(),

                        Textarea::make('reason_for_contact')
                            ->rows(3)
                            ->columnSpanFull()
                            ->required(),

                        Toggle::make('safeguarding_flags')
                            ->onColor('danger')
                            ->onIcon('heroicon-m-exclamation-triangle')
                            ->label('Safeguarding Flags'),
                    ])
                    ->columns(2),

                Section::make('Actions & Referrals')
                    ->schema([
                        Textarea::make('advice_given')
                            ->rows(3),

                        Textarea::make('action_taken')
                            ->rows(3),

                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->default(auth()->user()?->id)
                            ->required()
                            ->label('Staff Member'),
                    ])
                    ->columns(3),
            ]);
    }
}
