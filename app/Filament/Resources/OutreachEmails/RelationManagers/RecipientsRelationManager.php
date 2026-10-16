<?php

declare(strict_types=1);

namespace App\Filament\Resources\OutreachEmails\RelationManagers;

use App\Enums\OutreachRecipientStatus;
use App\Filament\Resources\CompanyResource;
use App\Models\OutreachEmailRecipient;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Who an outreach email went to and whether each send succeeded. Read-only.
 */
final class RecipientsRelationManager extends RelationManager
{
    protected static string $relationship = 'recipients';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-users';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('view', $ownerRecord) ?? false;
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('company.name')
                    ->label('Company')
                    ->url(fn (OutreachEmailRecipient $record): string => CompanyResource::getUrl('view', ['record' => $record->company_id])),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('sent_at')
                    ->label('Sent')
                    ->dateTime()
                    ->placeholder('-'),
                TextColumn::make('error')
                    ->limit(60)
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('status')
                    ->options(OutreachRecipientStatus::class),
            ]);
    }
}
