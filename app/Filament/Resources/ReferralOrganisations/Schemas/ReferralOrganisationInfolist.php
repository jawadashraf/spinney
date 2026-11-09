<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReferralOrganisations\Schemas;

use App\Models\ReferralOrganisation;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ReferralOrganisationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Organisation & Service Details')
                    ->compact()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Organisation / Service')
                            ->weight('bold')
                            ->size('lg')
                            ->columnSpan(2),

                        TextEntry::make('priority')
                            ->badge()
                            ->columnSpan(1),

                        TextEntry::make('area_of_support')
                            ->label('Area of Support')
                            ->badge()
                            ->color('gray')
                            ->columnSpan(3),

                        TextEntry::make('what_they_help_with')
                            ->label('What They Help With')
                            ->columnSpanFull(),
                    ]),

                Section::make('Contact & Location')
                    ->compact()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('telephone')
                            ->label('Telephone')
                            ->icon('heroicon-m-phone')
                            ->url(fn (ReferralOrganisation $record): ?string => filled($record->telephone) ? 'tel:'.preg_replace('/[^0-9+]/', '', (string) $record->telephone) : null)
                            ->copyable()
                            ->placeholder('None recorded'),

                        TextEntry::make('email_or_text')
                            ->label('Email / SMS')
                            ->icon('heroicon-m-envelope')
                            ->copyable()
                            ->placeholder('None recorded'),

                        TextEntry::make('website_url')
                            ->label('Official Website')
                            ->icon('heroicon-m-globe-alt')
                            ->url(fn (ReferralOrganisation $record): ?string => $record->website_url, shouldOpenInNewTab: true)
                            ->placeholder('None'),

                        TextEntry::make('address')
                            ->label('Address / Area')
                            ->icon('heroicon-m-map-pin')
                            ->columnSpanFull()
                            ->placeholder('Leicester / Not specified'),
                    ]),

                Section::make('Volunteer Advice & Key Reminders (CRITICAL)')
                    ->compact()
                    ->columns(1)
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('warning')
                    ->schema([
                        TextEntry::make('volunteer_notes')
                            ->hiddenLabel()
                            ->weight('medium')
                            ->placeholder('No specific volunteer cautions recorded.'),
                    ]),

                Section::make('How to Access & Refer')
                    ->compact()
                    ->columns(1)
                    ->schema([
                        TextEntry::make('how_to_refer')
                            ->hiddenLabel()
                            ->placeholder('Self-referral or contact directly.'),

                        TextEntry::make('last_checked_at')
                            ->label('Information Last Verified')
                            ->color('gray'),
                    ]),
            ]);
    }
}
