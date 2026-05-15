<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SessionType: string implements HasColor, HasLabel
{
    case INDIVIDUAL = 'individual';
    case GROUP = 'group';

    public function getLabel(): string
    {
        return match ($this) {
            self::INDIVIDUAL => 'Individual',
            self::GROUP => 'Group',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::INDIVIDUAL => 'primary',
            self::GROUP => 'warning',
        };
    }
}
