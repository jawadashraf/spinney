<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum GiftAidStatus: string implements HasColor, HasLabel
{
    case NotEligible = 'not_eligible';
    case Eligible = 'eligible';
    case Claimed = 'claimed';

    public function getLabel(): string
    {
        return match ($this) {
            self::NotEligible => 'Not eligible',
            self::Eligible => 'Eligible',
            self::Claimed => 'Claimed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NotEligible => 'gray',
            self::Eligible => 'warning',
            self::Claimed => 'success',
        };
    }

    /**
     * Whether the donation counts as Gift Aid, so it needs a declaration covering it.
     */
    public function needsDeclaration(): bool
    {
        return $this !== self::NotEligible;
    }
}
