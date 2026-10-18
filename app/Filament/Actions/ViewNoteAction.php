<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Models\Note;
use App\Support\RestrictedNoteAccess;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\View\View;

/**
 * Opens a note in a read-only modal. Lists show restricted notes by title only, so opening one here is the
 * point where its content is read, and that access is logged.
 */
final class ViewNoteAction extends Action
{
    public static function getDefaultName(): string
    {
        return 'viewNote';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(fn (Note $record): string => $record->isRestricted() ? 'Open restricted note' : 'Open')
            ->icon(fn (Note $record): string => $record->isRestricted() ? 'heroicon-o-lock-open' : 'heroicon-o-eye')
            ->color('gray')
            ->modalHeading(fn (Note $record): string => $record->title)
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->authorize(fn (Note $record): bool => auth()->user()?->can('view', $record) ?? false)
            ->mountUsing(fn (Note $record) => RestrictedNoteAccess::record($record))
            ->modalContent(fn (Note $record): View => view('filament.notes.view-note', ['note' => $record]));
    }
}
