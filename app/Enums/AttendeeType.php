<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AttendeeType: string implements HasColor, HasIcon, HasLabel
{
    case SERVICE_USER = 'service_user';
    case EXTERNAL = 'external';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SERVICE_USER => 'Service User',
            self::EXTERNAL => 'External',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SERVICE_USER => 'primary',
            self::EXTERNAL => 'secondary',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::SERVICE_USER => 'heroicon-m-user',
            self::EXTERNAL => 'heroicon-m-users',
        };
    }
}
