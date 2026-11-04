<?php

declare(strict_types=1);

namespace App\Filament\Resources\PhoneCalls\Actions;

use App\Models\PbxCallLog;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

/**
 * Takes a call off the "needs notes" list when there is nothing worth writing down.
 */
final class SkipCallNotesAction
{
    public static function make(): Action
    {
        return Action::make('skipNotes')
            ->label('No notes needed')
            ->icon(Heroicon::OutlinedCheck)
            ->color('gray')
            ->visible(fn (PbxCallLog $record): bool => $record->needsNotes()
                && ! $record->hasLiaisonCallAwaitingNotes()
                && $record->isVisibleTo(auth()->user() instanceof User ? auth()->user() : null))
            ->action(fn (PbxCallLog $record): bool => $record->forceFill(['notes_skipped_at' => now()])->save());
    }
}
