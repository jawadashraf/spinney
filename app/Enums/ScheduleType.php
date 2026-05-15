<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Zap\Enums\ScheduleTypes;

enum ScheduleType: string implements HasColor, HasIcon, HasLabel
{
    case AVAILABILITY = 'availability';
    case APPOINTMENT = 'appointment';
    case BLOCKED = 'blocked';
    case CUSTOM = 'custom';

    public function getLabel(): string
    {
        return match ($this) {
            self::AVAILABILITY => 'Availability',
            self::APPOINTMENT => 'Appointment',
            self::BLOCKED => 'Blocked',
            self::CUSTOM => 'Custom',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::AVAILABILITY => 'success',
            self::APPOINTMENT => 'info',
            self::BLOCKED => 'danger',
            self::CUSTOM => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::AVAILABILITY => 'heroicon-o-clock',
            self::APPOINTMENT => 'heroicon-o-calendar',
            self::BLOCKED => 'heroicon-o-no-symbol',
            self::CUSTOM => 'heroicon-o-cog-6-tooth',
        };
    }

    public function toZapScheduleTypes(): ScheduleTypes
    {
        return ScheduleTypes::from($this->value);
    }

    public static function fromZapScheduleTypes(ScheduleTypes $type): self
    {
        return self::from($type->value);
    }
}
