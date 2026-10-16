<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyResource\RelationManagers;

use App\Filament\Resources\OutreachEmails\OutreachEmailResource;
use App\Models\OutreachEmail;
use App\Models\OutreachEmailRecipient;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Outreach emails sent to a company's general email or its contacts. Read-only.
 */
final class OutreachEmailsRelationManager extends RelationManager
{
    protected static string $relationship = 'outreachRecipients';

    protected static ?string $title = 'Emails';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-envelope';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('viewAny', OutreachEmail::class) ?? false;
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->modifyQueryUsing(fn ($query) => $query->with('outreachEmail'))
            ->columns([
                TextColumn::make('outreachEmail.subject')
                    ->label('Subject')
                    ->limit(60)
                    ->url(fn (OutreachEmailRecipient $record): string => OutreachEmailResource::getUrl('view', ['record' => $record->outreach_email_id])),
                TextColumn::make('name')
                    ->label('To'),
                TextColumn::make('email'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('sent_at')
                    ->label('Sent')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
