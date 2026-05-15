<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ScheduleFrequency: string implements HasColor, HasLabel
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case WEEKLY_ODD = 'weekly_odd';
    case WEEKLY_EVEN = 'weekly_even';
    case BIWEEKLY = 'biweekly';
    case MONTHLY = 'monthly';
    case BIMONTHLY = 'bimonthly';
    case QUARTERLY = 'quarterly';
    case SEMIANNUALLY = 'semiannually';
    case ANNUALLY = 'annually';

    public function getLabel(): string
    {
        return match ($this) {
            self::DAILY => 'Daily',
            self::WEEKLY => 'Weekly',
            self::WEEKLY_ODD => 'Weekly (Odd weeks)',
            self::WEEKLY_EVEN => 'Weekly (Even weeks)',
            self::BIWEEKLY => 'Biweekly',
            self::MONTHLY => 'Monthly',
            self::BIMONTHLY => 'Bimonthly',
            self::QUARTERLY => 'Quarterly',
            self::SEMIANNUALLY => 'Semiannually',
            self::ANNUALLY => 'Annually',
        };
    }

    public function getColor(): string
    {
        return 'primary';
    }
}
