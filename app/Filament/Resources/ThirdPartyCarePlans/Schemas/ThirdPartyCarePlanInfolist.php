<?php

declare(strict_types=1);

namespace App\Filament\Resources\ThirdPartyCarePlans\Schemas;

use App\Enums\ThirdPartyCarePlanStatus;
use App\Models\ThirdPartyCarePlan;
use App\Support\CustomFields;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

final class ThirdPartyCarePlanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Provider Details')
                    ->schema([
                        TextEntry::make('provider_name')
                            ->label('Provider Name'),
                        TextEntry::make('provider_contact.email')
                            ->label('Email')
                            ->placeholder('Not provided'),
                        TextEntry::make('provider_contact.phone')
                            ->label('Phone')
                            ->placeholder('Not provided'),
                        TextEntry::make('provider_contact.address')
                            ->label('Address')
                            ->placeholder('Not provided'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Service User')
                    ->schema([
                        TextEntry::make('serviceUser.name')
                            ->label('Service User'),
                        TextEntry::make('serviceUser.email')
                            ->label('Email')
                            ->placeholder('Not provided'),
                        TextEntry::make('serviceUser.phone')
                            ->label('Phone')
                            ->placeholder('Not provided'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Status & Timeline')
                    ->schema([
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (ThirdPartyCarePlanStatus $state): string => match ($state) {
                                ThirdPartyCarePlanStatus::PENDING => 'warning',
                                ThirdPartyCarePlanStatus::IN_PROGRESS => 'info',
                                ThirdPartyCarePlanStatus::COMPLETED => 'success',
                                ThirdPartyCarePlanStatus::CANCELLED => 'danger',
                            }),
                        TextEntry::make('referral_date')
                            ->date(),
                        TextEntry::make('start_date')
                            ->date()
                            ->placeholder('Not started'),
                        TextEntry::make('end_date')
                            ->date()
                            ->placeholder('Not completed'),
                        TextEntry::make('managers.name')
                            ->label('Assigned Managers')
                            ->limit(3)
                            ->badge(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                CustomFields::infolist()
                    ->forSchema($schema)
                    ->build()
                    ->columnSpanFull(),

                Section::make('Notes')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Notes')
                            ->prose()
                            ->placeholder('No notes'),
                        TextEntry::make('internal_notes')
                            ->label('Internal Notes')
                            ->prose()
                            ->placeholder('No internal notes')
                            ->visible(fn (): bool => Auth::user()->can('viewInternalNotes:ThirdPartyCarePlan')),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Section::make('Metadata')
                    ->schema([
                        TextEntry::make('creator.name')
                            ->label('Created By'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                        TextEntry::make('deleted_at')
                            ->dateTime()
                            ->visible(fn (ThirdPartyCarePlan $record): bool => $record->trashed()),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
