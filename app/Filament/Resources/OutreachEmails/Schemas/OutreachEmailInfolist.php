<?php

declare(strict_types=1);

namespace App\Filament\Resources\OutreachEmails\Schemas;

use App\Actions\Outreach\ResolveOutreachRecipients;
use App\Models\CompanyType;
use App\Models\MailingGroup;
use App\Models\OutreachEmail;
use App\Support\MailQuota;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class OutreachEmailInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('sent_at')
                            ->label('Sent')
                            ->dateTime()
                            ->placeholder('Not sent yet'),
                        TextEntry::make('sentBy.name')
                            ->label('Sent by')
                            ->placeholder('-'),
                        TextEntry::make('audience')
                            ->label('Recipients')
                            ->state(fn (OutreachEmail $record): string => self::audience($record))
                            ->columnSpanFull(),
                        TextEntry::make('quota')
                            ->label('Sending allowance')
                            ->state(fn (): string => app(MailQuota::class)->summary())
                            ->visible(fn (OutreachEmail $record): bool => $record->isDraft())
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                Section::make('Email')
                    ->schema([
                        TextEntry::make('subject'),
                        TextEntry::make('body')
                            ->label('Message')
                            ->html()
                            ->prose(),
                        TextEntry::make('attachment_names')
                            ->label('Attachments')
                            ->state(fn (OutreachEmail $record): array => array_values($record->attachment_names ?? []))
                            ->badge()
                            ->placeholder('None'),
                    ]),
            ])
            ->columns(1);
    }

    private static function audience(OutreachEmail $email): string
    {
        $parts = array_filter([
            filled($email->mailing_group_ids) ? 'Groups: '.MailingGroup::query()->whereKey($email->mailing_group_ids)->pluck('name')->implode(', ') : null,
            filled($email->company_type_ids) ? 'Types: '.CompanyType::query()->whereKey($email->company_type_ids)->pluck('name')->implode(', ') : null,
            filled($email->company_ids) ? count($email->company_ids).' picked companies' : null,
        ]);

        $channels = array_filter([
            $email->send_to_general_emails ? 'general emails' : null,
            $email->send_to_contacts ? 'mailing-list contacts' : null,
        ]);

        $count = $email->isDraft()
            ? app(ResolveOutreachRecipients::class)->handle($email)->count().' addresses'
            : $email->recipients()->count().' addresses';

        return implode(' · ', [...$parts, 'To '.implode(' and ', $channels), $count]);
    }
}
