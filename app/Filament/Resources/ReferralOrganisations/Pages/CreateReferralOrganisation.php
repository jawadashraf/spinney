<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReferralOrganisations\Pages;

use App\Filament\Resources\ReferralOrganisations\ReferralOrganisationResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateReferralOrganisation extends CreateRecord
{
    protected static string $resource = ReferralOrganisationResource::class;
}
