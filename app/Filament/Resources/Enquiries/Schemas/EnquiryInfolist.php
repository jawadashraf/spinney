<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enquiries\Schemas;

use App\Filament\Resources\Enquiries\EnquiryResource;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

final class EnquiryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Enquiry Overview')
                    ->icon(Heroicon::User)
                    ->schema([
                        TextEntry::make('direction')
                            ->badge(),

                        TextEntry::make('call_type')
                            ->badge()
                            ->placeholder('—'),

                        TextEntry::make('source')
                            ->badge(),

                        TextEntry::make('people.name')
                            ->label('Caller')
                            ->default('Anonymous'),

                        TextEntry::make('caller_type')
                            ->badge()
                            ->label('Caller Type'),

                        TextEntry::make('caller_note')
                            ->label('Caller Notes')
                            ->placeholder('—')
                            ->visible(fn ($record): bool => $record->caller_note !== null)
                            ->columnSpanFull(),

                        TextEntry::make('phone')
                            ->label('Phone')
                            ->placeholder('Not provided'),

                        TextEntry::make('category')
                            ->badge(),

                        TextEntry::make('occurred_at')
                            ->dateTime(),

                        TextEntry::make('status')
                            ->badge(),

                        TextEntry::make('converted_at')
                            ->dateTime()
                            ->visible(fn ($record): bool => $record?->converted_at !== null),
                    ])
                    ->columns(2),

                Section::make('Assignment & Follow-up')
                    ->icon(Heroicon::UserGroup)
                    ->schema([
                        TextEntry::make('department.name')
                            ->label('Department')
                            ->placeholder('Unassigned')
                            ->badge(),

                        TextEntry::make('due_date')
                            ->dateTime()
                            ->label('Due Date')
                            ->placeholder('Not set')
                            ->visible(fn ($record): bool => $record->due_date !== null),

                        TextEntry::make('outcome')
                            ->badge()
                            ->placeholder('—')
                            ->visible(fn ($record): bool => $record->outcome !== null),

                        TextEntry::make('parentEnquiry.reason_for_contact')
                            ->label('Originating Enquiry')
                            ->limit(100)
                            ->url(fn ($record) => $record->parentEnquiry ? EnquiryResource::getUrl('view', ['record' => $record->parentEnquiry]) : null)
                            ->visible(fn ($record): bool => $record->parent_enquiry_id !== null),

                        TextEntry::make('childEnquiriesCount')
                            ->label('Follow-up Enquiries')
                            ->state(fn ($record): int => $record->childEnquiries()->count())
                            ->visible(fn ($record): bool => $record->childEnquiries()->exists()),
                    ])
                    ->columns(2),

                Section::make('Safeguarding & Risk')
                    ->icon(Heroicon::ShieldExclamation)
                    ->schema([
                        IconEntry::make('safeguarding_flags')
                            ->boolean()
                            ->label('Safeguarding Flags')
                            ->color(fn (bool $state): string => $state ? 'danger' : 'gray'),

                        TextEntry::make('risk_flags')
                            ->label('Risk Flags')
                            ->placeholder('None recorded')
                            ->prose(),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(fn ($record): bool => ! $record->safeguarding_flags && empty($record->risk_flags)),

                Section::make('Narrative')
                    ->icon(Heroicon::DocumentText)
                    ->schema([
                        TextEntry::make('reason_for_contact')
                            ->columnSpanFull()
                            ->prose(),

                        TextEntry::make('advice_given')
                            ->placeholder('Not recorded')
                            ->prose(),

                        TextEntry::make('action_taken')
                            ->placeholder('Not recorded')
                            ->prose(),
                    ])
                    ->columns(2),

                Section::make('Referral')
                    ->icon(Heroicon::ArrowTopRightOnSquare)
                    ->schema([
                        TextEntry::make('referral_type')
                            ->badge()
                            ->label('Referral Type')
                            ->formatStateUsing(fn ($state): string => $state ? ucfirst($state) : '—')
                            ->visible(fn ($record): bool => $record->referral_type !== null),

                        TextEntry::make('referral_destination')
                            ->label('Referral Destination')
                            ->visible(fn ($record): bool => $record->referral_destination !== null),
                    ])
                    ->columns(2)
                    ->visible(fn ($record): bool => $record->referral_type !== null),

                Section::make('Staff & Timeline')
                    ->icon(Heroicon::User)
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
