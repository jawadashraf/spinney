<?php

declare(strict_types=1);

namespace App\Filament\Resources\Calls\Schemas;

use App\Filament\Resources\Calls\CallResource;
use App\Models\Call;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

final class CallInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Call')
                    ->icon(Heroicon::OutlinedPhone)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('serviceUser.name')
                            ->label('Service user'),
                        TextEntry::make('serviceUser.phone')
                            ->label('Phone')
                            ->placeholder('Not recorded'),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('due_at')
                            ->label('Due')
                            ->dateTime('d M Y, H:i')
                            ->color(fn (Call $record): ?string => $record->isOverdue() ? 'danger' : null),
                        TextEntry::make('original_due_at')
                            ->label('Originally due')
                            ->dateTime('d M Y, H:i')
                            ->visible(fn (Call $record): bool => ! $record->original_due_at->equalTo($record->due_at)),
                        TextEntry::make('assignee.name')
                            ->label('Liaison')
                            ->placeholder('Unassigned'),
                        TextEntry::make('plan.frequency')
                            ->label('Call plan')
                            ->badge()
                            ->placeholder('Ad-hoc'),
                        TextEntry::make('attempt_count')
                            ->label('Attempts')
                            ->formatStateUsing(fn (Call $record): string => $record->attempt_count.' of '.$record->maxAttempts()),
                        TextEntry::make('reason')
                            ->label('Reason for call')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
                Section::make('Outcome')
                    ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                    ->columns(3)
                    ->visible(fn (Call $record): bool => $record->attempt_count > 0)
                    ->schema([
                        TextEntry::make('outcome')
                            ->label('Last outcome')
                            ->badge(),
                        TextEntry::make('completed_at')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('—'),
                        TextEntry::make('completedBy.name')
                            ->label('Completed by')
                            ->placeholder('—'),
                        TextEntry::make('notes')
                            ->label('Call notes')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('action_required')
                            ->label('Action required')
                            ->placeholder('None')
                            ->columnSpanFull(),
                        TextEntry::make('next_follow_up_at')
                            ->label('Next follow-up')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('None'),
                        IconEntry::make('safeguarding_raised')
                            ->label('Concern raised')
                            ->boolean(),
                    ]),
                Section::make('Attempts')
                    ->icon(Heroicon::OutlinedListBullet)
                    ->visible(fn (Call $record): bool => $record->attempt_count > 0)
                    ->schema([
                        RepeatableEntry::make('attempts')
                            ->hiddenLabel()
                            ->columns(3)
                            ->schema([
                                TextEntry::make('attempted_at')
                                    ->label('When')
                                    ->dateTime('d M Y, H:i'),
                                TextEntry::make('user.name')
                                    ->label('By')
                                    ->placeholder('—'),
                                TextEntry::make('outcome')
                                    ->badge(),
                                TextEntry::make('notes')
                                    ->placeholder('—')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Follow-up chain')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('parentCall.due_at')
                            ->label('Previous call')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('—')
                            ->url(fn (Call $record): ?string => $record->parent_call_id ? CallResource::getUrl('view', ['record' => $record->parent_call_id]) : null),
                        TextEntry::make('followUpCall.due_at')
                            ->label('Follow-up call')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('—')
                            ->url(fn (Call $record): ?string => $record->followUpCall ? CallResource::getUrl('view', ['record' => $record->followUpCall]) : null),
                    ]),
            ]);
    }
}
