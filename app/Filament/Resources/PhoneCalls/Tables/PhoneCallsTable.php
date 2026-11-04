<?php

declare(strict_types=1);

namespace App\Filament\Resources\PhoneCalls\Tables;

use App\Filament\Resources\Calls\CallResource;
use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\PeopleResource;
use App\Filament\Resources\ServiceUsers\ServiceUserResource;
use App\Models\Call;
use App\Models\PbxCallLog;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class PhoneCallsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['user', 'person', 'company']))
            ->defaultSort('started_at', 'desc')
            ->emptyStateHeading('No phone calls yet')
            ->emptyStateDescription('Calls made or received on the phone system appear here.')
            ->columns([
                TextColumn::make('started_at')
                    ->label('When')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                TextColumn::make('contact')
                    ->label('Contact')
                    ->state(fn (PbxCallLog $record): ?string => $record->person->name ?? $record->company?->name)
                    ->placeholder('Unmatched')
                    ->description(fn (PbxCallLog $record): ?string => $record->person?->is_service_user ? 'Service user' : null)
                    ->url(fn (PbxCallLog $record): ?string => self::contactUrl($record))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where(function (Builder $query) use ($search): void {
                        $query->whereHas('person', fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('company', fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%"));
                    })),
                TextColumn::make('contact_number')
                    ->label('Number')
                    ->placeholder('Withheld')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('direction')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => $state === PbxCallLog::DIRECTION_INBOUND ? 'info' : 'gray')
                    ->icon(fn (string $state): string => $state === PbxCallLog::DIRECTION_INBOUND ? 'heroicon-o-phone-arrow-down-left' : 'heroicon-o-phone-arrow-up-right'),
                TextColumn::make('user.name')
                    ->label('Staff')
                    ->placeholder('Unmapped extension'),
                TextColumn::make('disposition')
                    ->label('Outcome')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst(mb_strtolower($state)))
                    ->color(fn (PbxCallLog $record): string => $record->isAnswered() ? 'success' : 'gray'),
                TextColumn::make('talk_duration_sec')
                    ->label('Talk time')
                    ->formatStateUsing(fn (PbxCallLog $record): string => $record->formattedTalkDuration()),
                TextColumn::make('recording_url')
                    ->label('Recording')
                    ->formatStateUsing(fn (): string => 'Play')
                    ->url(fn (PbxCallLog $record): ?string => $record->recording_url, shouldOpenInNewTab: true)
                    ->color('primary')
                    ->placeholder('—'),
                TextColumn::make('result')
                    ->label('Liaison call')
                    ->badge()
                    ->url(fn (PbxCallLog $record): ?string => $record->call_id !== null ? CallResource::getUrl('view', ['record' => $record->call_id]) : null),
            ])
            ->filters([
                SelectFilter::make('direction')
                    ->options([
                        PbxCallLog::DIRECTION_INBOUND => 'Inbound',
                        PbxCallLog::DIRECTION_OUTBOUND => 'Outbound',
                    ]),
                TernaryFilter::make('matched')
                    ->label('Contact')
                    ->placeholder('All calls')
                    ->trueLabel('Matched to a contact')
                    ->falseLabel('Unmatched')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->where(fn (Builder $query): Builder => $query->whereNotNull('people_id')->orWhereNotNull('company_id')->orWhereNotNull('enquiry_id')),
                        false: fn (Builder $query): Builder => $query->whereNull('people_id')->whereNull('company_id')->whereNull('enquiry_id'),
                    ),
                SelectFilter::make('user_id')
                    ->label('Staff')
                    ->relationship('user', 'name')
                    ->visible(fn (): bool => auth()->user() instanceof User && Call::userSeesAllCalls(auth()->user())),
            ]);
    }

    /**
     * Service users open on their own page; other people and companies only if the user may view them.
     */
    private static function contactUrl(PbxCallLog $record): ?string
    {
        if ($record->person !== null) {
            if ($record->person->is_service_user) {
                return ServiceUserResource::getUrl('view', ['record' => $record->people_id]);
            }

            return PeopleResource::canView($record->person) ? PeopleResource::getUrl('view', ['record' => $record->people_id]) : null;
        }

        if ($record->company !== null && CompanyResource::canView($record->company)) {
            return CompanyResource::getUrl('view', ['record' => $record->company_id]);
        }

        return null;
    }
}
