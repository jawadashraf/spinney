<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum CallerType: string implements HasColor, HasIcon, HasLabel
{
    case ANONYMOUS = 'anonymous';
    case KNOWN_PERSON = 'known_person';
    case SERVICE_USER = 'service_user';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ANONYMOUS => 'Anonymous',
            self::KNOWN_PERSON => 'Known Person',
            self::SERVICE_USER => 'Service User',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ANONYMOUS => 'gray',
            self::KNOWN_PERSON => 'info',
            self::SERVICE_USER => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::ANONYMOUS => 'heroicon-o-user-minus',
            self::KNOWN_PERSON => 'heroicon-o-user',
            self::SERVICE_USER => 'heroicon-o-user-group',
        };
    }
}
