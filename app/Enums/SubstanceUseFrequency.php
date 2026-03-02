<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SubstanceUseFrequency: string implements HasLabel
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case FORTNIGHTLY = 'fortnightly';
    case MONTHLY = 'monthly';
    case NOT_USING = 'not_currently_using';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DAILY => 'Daily',
            self::WEEKLY => 'Weekly',
            self::FORTNIGHTLY => 'Fortnightly',
            self::MONTHLY => 'Monthly',
            self::NOT_USING => 'Not currently Using',
        };
    }
}
