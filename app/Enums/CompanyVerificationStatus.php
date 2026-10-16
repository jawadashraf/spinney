<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Whether staff have confirmed a directory record's details, as opposed to taking them from a public listing.
 */
enum CompanyVerificationStatus: string implements HasColor, HasLabel
{
    case Unverified = 'unverified';
    case NeedsChecking = 'needs_checking';
    case Verified = 'verified';

    public function getLabel(): string
    {
        return match ($this) {
            self::Unverified => 'Unverified',
            self::NeedsChecking => 'Needs checking',
            self::Verified => 'Verified',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Unverified => 'gray',
            self::NeedsChecking => 'warning',
            self::Verified => 'success',
        };
    }
}
