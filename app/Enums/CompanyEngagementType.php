<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CompanyEngagementType: string implements HasColor, HasLabel
{
    case Visit = 'visit';
    case Talk = 'talk';
    case Event = 'event';
    case Meeting = 'meeting';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Visit => 'Visit',
            self::Talk => 'Talk',
            self::Event => 'Event',
            self::Meeting => 'Meeting',
            self::Other => 'Other',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Visit => 'info',
            self::Talk => 'primary',
            self::Event => 'success',
            self::Meeting => 'warning',
            self::Other => 'gray',
        };
    }
}
