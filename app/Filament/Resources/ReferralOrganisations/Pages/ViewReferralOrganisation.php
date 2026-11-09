<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReferralOrganisations\Pages;

use App\Filament\Resources\ReferralOrganisations\ReferralOrganisationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewReferralOrganisation extends ViewRecord
{
    protected static string $resource = ReferralOrganisationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->slideOver(),
        ];
    }
}
