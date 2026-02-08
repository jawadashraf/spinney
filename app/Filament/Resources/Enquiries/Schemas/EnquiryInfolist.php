<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enquiries\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class EnquiryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Enquiry Overview')
                    ->schema([
                        TextEntry::make('people.name')
                            ->label('Caller'),

                        TextEntry::make('category')
                            ->badge(),

                        TextEntry::make('occurred_at')
                            ->dateTime(),

                        IconEntry::make('safeguarding_flags')
                            ->boolean()
                            ->label('Safeguarding'),
                    ])
                    ->columns(2),

                Section::make('Narrative')
                    ->schema([
                        TextEntry::make('reason_for_contact')
                            ->columnSpanFull(),

                        TextEntry::make('advice_given'),

                        TextEntry::make('action_taken'),
                    ])
                    ->columns(2),

                Section::make('Staff Information')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Staff Member'),

                        TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Logged On'),
                    ])
                    ->columns(2),
            ]);
    }
}
