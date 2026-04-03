<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AppointmentStatus: string implements HasColor, HasLabel
{
    case SCHEDULED = 'scheduled';
    case LOCKED = 'locked';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::SCHEDULED => 'Scheduled',
            self::LOCKED => 'Locked',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SCHEDULED => 'warning',
            self::LOCKED => 'success',
            self::CANCELLED => 'danger',
        };
    }
}
