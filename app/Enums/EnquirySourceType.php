<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum EnquirySourceType: string implements HasColor, HasIcon, HasLabel
{
    case PHONE = 'phone';
    case WALK_IN = 'walk_in';
    case EMAIL = 'email';
    case ONLINE = 'online';
    case REFERRAL = 'referral';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PHONE => 'Phone',
            self::WALK_IN => 'Walk-in',
            self::EMAIL => 'Email',
            self::ONLINE => 'Online',
            self::REFERRAL => 'Referral',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PHONE => 'primary',
            self::WALK_IN => 'info',
            self::EMAIL => 'success',
            self::ONLINE => 'warning',
            self::REFERRAL => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PHONE => 'heroicon-o-phone',
            self::WALK_IN => 'heroicon-o-user',
            self::EMAIL => 'heroicon-o-envelope',
            self::ONLINE => 'heroicon-o-globe-alt',
            self::REFERRAL => 'heroicon-o-arrow-right-on-rectangle',
        };
    }
}
