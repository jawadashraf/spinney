<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Note;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

/**
 * Records each time someone opens a restricted note (AC NT-004, AT-002). Only the fact of access is logged,
 * never the note's content.
 */
final class RestrictedNoteAccess
{
    public static function record(Note $note): void
    {
        if (! $note->isRestricted()) {
            return;
        }

        $user = auth()->user();

        activity()
            ->performedOn($note)
            ->causedBy($user instanceof User ? $user : null)
            ->event('viewed')
            ->withProperties(['title' => $note->title])
            ->tap(fn (Activity $activity) => $activity->setAttribute('team_id', $note->team_id))
            ->log('Restricted note viewed');
    }
}
