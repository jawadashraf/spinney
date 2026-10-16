<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\CompanyType;
use Laravel\Jetstream\Events\TeamCreated;

final readonly class CreateDefaultCompanyTypes
{
    public function handle(TeamCreated $event): void
    {
        CompanyType::createDefaultsFor($event->team);
    }
}
