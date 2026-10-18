<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DonationPurpose: string implements HasLabel
{
    case General = 'general';
    case Zakat = 'zakat';
    case Sadaqah = 'sadaqah';
    case FoodBank = 'food_bank';
    case Appeal = 'appeal';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::General => 'General funds',
            self::Zakat => 'Zakat',
            self::Sadaqah => 'Sadaqah',
            self::FoodBank => 'Food bank',
            self::Appeal => 'Appeal or campaign',
            self::Other => 'Other',
        };
    }
}
