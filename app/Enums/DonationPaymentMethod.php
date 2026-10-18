<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DonationPaymentMethod: string implements HasLabel
{
    case Cash = 'cash';
    case Card = 'card';
    case BankTransfer = 'bank_transfer';
    case StandingOrder = 'standing_order';
    case Cheque = 'cheque';
    case Online = 'online';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Card => 'Card',
            self::BankTransfer => 'Bank transfer',
            self::StandingOrder => 'Standing order',
            self::Cheque => 'Cheque',
            self::Online => 'Online',
        };
    }
}
