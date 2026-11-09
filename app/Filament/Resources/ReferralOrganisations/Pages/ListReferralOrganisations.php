<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReferralOrganisations\Pages;

use App\Filament\Resources\ReferralOrganisations\ReferralOrganisationResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

final class ListReferralOrganisations extends ListRecords
{
    protected static string $resource = ReferralOrganisationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('volunteerGuide')
                ->label('Volunteer Referral Guide')
                ->icon('heroicon-o-information-circle')
                ->color('info')
                ->slideOver()
                ->modalHeading('Spinney Hill Drug Support — Volunteer Referral Guide')
                ->modalWidth(Width::Large)
                ->modalContent(fn () => view('filament.resources.referral-organisations.volunteer-guide-modal'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),
            CreateAction::make()
                ->slideOver(),
        ];
    }
}
