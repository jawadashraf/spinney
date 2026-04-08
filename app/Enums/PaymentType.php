<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PaymentType: string implements HasColor, HasIcon, HasLabel
{
    case FREE = 'free';
    case PAID = 'paid';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::FREE => 'Free',
            self::PAID => 'Paid',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::FREE => 'success',
            self::PAID => 'info',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::FREE => 'heroicon-m-gift',
            self::PAID => 'heroicon-m-currency-pound',
        };
    }
}
