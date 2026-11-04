<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Note;
use App\Models\PbxCallLog;
use App\Models\User;

final readonly class NoteObserver
{
    public function creating(Note $note): void
    {
        if (auth()->check()) {
            /** @var User $user */
            $user = auth()->user();
            $note->creator_id = $user->getKey();
            $note->team_id = $user->current_team_id;
        }
    }

    public function saved(Note $note): void
    {
        $note->invalidateRelatedSummaries();
    }

    /**
     * A phone call whose note is deleted needs notes again.
     */
    public function deleted(Note $note): void
    {
        $note->invalidateRelatedSummaries();

        PbxCallLog::query()->where('note_id', $note->getKey())->update(['note_id' => null]);
    }
}
