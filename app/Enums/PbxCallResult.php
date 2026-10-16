<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * What Spinney did with a call reported by the phone system.
 */
enum PbxCallResult: string implements HasLabel
{
    case AwaitingNotes = 'awaiting_notes';
    case AttemptRecorded = 'attempt_recorded';
    case LoggedOnly = 'logged_only';

    public function getLabel(): string
    {
        return match ($this) {
            self::AwaitingNotes => 'Answered – awaiting notes',
            self::AttemptRecorded => 'Attempt recorded',
            self::LoggedOnly => 'Logged only',
        };
    }
}
