<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceUsers\Concerns;

trait HasServiceUserTabNavigation
{
    public string $activeServiceUserTab = 'demographics-consent';
}
