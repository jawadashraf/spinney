<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CallFrequency: string implements HasColor, HasLabel
{
    case Weekly = 'weekly';
    case Fortnightly = 'fortnightly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Custom = 'custom';

    public function getLabel(): string
    {
        return match ($this) {
            self::Weekly => 'Weekly',
            self::Fortnightly => 'Fortnightly',
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::Custom => 'Custom interval',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Weekly, self::Fortnightly => 'info',
            self::Monthly => 'primary',
            self::Quarterly => 'gray',
            self::Custom => 'warning',
        };
    }

    /**
     * Calculate the next due date from the given point in time, keeping the time of day.
     */
    public function nextDueFrom(CarbonInterface $from, ?int $intervalDays = null): CarbonImmutable
    {
        $from = CarbonImmutable::instance($from);

        return match ($this) {
            self::Weekly => $from->addDays(7),
            self::Fortnightly => $from->addDays(14),
            self::Monthly => $from->addMonthNoOverflow(),
            self::Quarterly => $from->addMonthsNoOverflow(3),
            self::Custom => $from->addDays(max(1, $intervalDays ?? 7)),
        };
    }
}
