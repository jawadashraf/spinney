<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Restricted notes (e.g. safeguarding) can only be read by managers and admins (AC NT-002, NT-003).
 */
enum NoteVisibility: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Standard = 'standard';
    case Restricted = 'restricted';

    public function getLabel(): string
    {
        return match ($this) {
            self::Standard => 'Standard',
            self::Restricted => 'Restricted',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Standard => 'Everyone who works with this record can read it.',
            self::Restricted => 'Only managers and admins can read it. Opening it is recorded in the audit log.',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Standard => 'gray',
            self::Restricted => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Standard => 'heroicon-o-document-text',
            self::Restricted => 'heroicon-o-lock-closed',
        };
    }
}
