<?php

declare(strict_types=1);

namespace App\Filament\Resources\UnmatchedCalls\Tables;

use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Filament\Resources\UnmatchedCalls\Actions\LinkToContactAction;
use App\Models\PbxCallLog;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class UnmatchedCallsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user'))
            ->defaultSort('started_at', 'desc')
            ->emptyStateHeading('No unmatched calls')
            ->emptyStateDescription('Calls from numbers that are not on anyone\'s record appear here.')
            ->columns([
                TextColumn::make('started_at')
                    ->label('When')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                TextColumn::make('contact_number')
                    ->label('Number')
                    ->placeholder('Withheld')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('direction')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => $state === PbxCallLog::DIRECTION_INBOUND ? 'info' : 'gray'),
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
            ])
            ->filters([
                SelectFilter::make('direction')
                    ->options([
                        PbxCallLog::DIRECTION_INBOUND => 'Inbound',
                        PbxCallLog::DIRECTION_OUTBOUND => 'Outbound',
                    ]),
            ])
            ->recordActions([
                LinkToContactAction::make(),
                ActionGroup::make([
                    Action::make('startEnquiry')
                        ->label('Start enquiry')
                        ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                        ->url(fn (PbxCallLog $record): string => EnquiryResource::getUrl('create', ['pbx_call' => $record->id]))
                        ->visible(fn (): bool => EnquiryResource::canCreate()),
                    Action::make('dismiss')
                        ->label('Dismiss')
                        ->icon(Heroicon::OutlinedTrash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Dismiss this call?')
                        ->modalDescription('The call log is removed from Spinney. The phone system keeps its own record.')
                        ->action(fn (PbxCallLog $record) => $record->delete()),
                ]),
            ])
            ->toolbarActions([
                BulkAction::make('dismiss')
                    ->label('Dismiss selected')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('The call logs are removed from Spinney. The phone system keeps its own records.')
                    ->action(fn (Collection $records) => $records->each->delete())
                    ->deselectRecordsAfterCompletion(),
            ]);
    }
}
