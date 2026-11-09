<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReferralOrganisations\Pages;

use App\Filament\Resources\ReferralOrganisations\ReferralOrganisationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditReferralOrganisation extends EditRecord
{
    protected static string $resource = ReferralOrganisationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
