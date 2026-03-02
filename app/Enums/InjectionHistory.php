<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum InjectionHistory: string implements HasLabel
{
    case NEVER = 'never';
    case PREVIOUSLY = 'previously';
    case CURRENTLY = 'currently';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NEVER => 'Never injected',
            self::PREVIOUSLY => 'Previously Injected',
            self::CURRENTLY => 'Currently injecting',
        };
    }
}
