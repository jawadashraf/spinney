<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum GiftAidDeclarationMethod: string implements HasLabel
{
    case Written = 'written';
    case Verbal = 'verbal';
    case Online = 'online';

    public function getLabel(): string
    {
        return match ($this) {
            self::Written => 'Written form',
            self::Verbal => 'Verbal (in person or by phone)',
            self::Online => 'Online form',
        };
    }
}
