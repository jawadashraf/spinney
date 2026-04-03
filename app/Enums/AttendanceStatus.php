<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AttendanceStatus: string implements HasColor, HasLabel
{
    case BOOKED = 'booked';
    case ATTENDED = 'attended';
    case NO_SHOW = 'no_show';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::BOOKED => 'Booked',
            self::ATTENDED => 'Attended',
            self::NO_SHOW => 'No Show',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::BOOKED => 'info',
            self::ATTENDED => 'success',
            self::NO_SHOW => 'danger',
            self::CANCELLED => 'warning',
        };
    }
}
