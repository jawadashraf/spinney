<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum EnquiryStatus: string implements HasColor, HasIcon, HasLabel
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case CONVERTED = 'converted';
    case CLOSED = 'closed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::IN_PROGRESS => 'In Progress',
            self::CONVERTED => 'Converted',
            self::CLOSED => 'Closed',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::OPEN => 'info',
            self::IN_PROGRESS => 'warning',
            self::CONVERTED => 'success',
            self::CLOSED => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::OPEN => 'heroicon-m-clock',
            self::IN_PROGRESS => 'heroicon-m-arrow-path',
            self::CONVERTED => 'heroicon-m-check-circle',
            self::CLOSED => 'heroicon-m-x-circle',
        };
    }
}
