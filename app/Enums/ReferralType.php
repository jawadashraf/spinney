<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ReferralType: string implements HasLabel
{
    case SELF = 'self';
    case AGENCY = 'agency';
    case FAMILY = 'family';
    case POLICE = 'police';
    case OTHER = 'other';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SELF => 'Self-Referral',
            self::AGENCY => 'Agency',
            self::FAMILY => 'Family Member',
            self::POLICE => 'Police',
            self::OTHER => 'Other',
        };
    }
}
