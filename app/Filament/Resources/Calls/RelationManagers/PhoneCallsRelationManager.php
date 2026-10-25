<?php

declare(strict_types=1);

namespace App\Filament\Resources\Calls\RelationManagers;

use App\Filament\Resources\Calls\CallResource;
use App\Models\PbxCallLog;
use App\Models\People;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only history of calls the phone system reported for a person or company.
 */
final class PhoneCallsRelationManager extends RelationManager
{
    protected static string $relationship = 'pbxCallLogs';

    protected static ?string $title = 'Phone calls';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-phone-arrow-down-left';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user'))
            ->defaultSort('started_at', 'desc')
            ->emptyStateHeading('No phone calls yet')
            ->emptyStateDescription('Calls made or received on the phone system appear here.')
            ->columns([
                TextColumn::make('started_at')
                    ->label('When')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
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
                    ->url(fn (PbxCallLog $record): ?string => $record->call_id !== null ? CallResource::getUrl('view', ['record' => $record->call_id]) : null)
                    ->visible(fn (): bool => $this->ownerIsServiceUser()),
            ]);
    }

    private function ownerIsServiceUser(): bool
    {
        $owner = $this->getOwnerRecord();

        return $owner instanceof People && $owner->is_service_user;
    }
}
