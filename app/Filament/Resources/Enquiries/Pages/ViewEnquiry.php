<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enquiries\Pages;

use App\Enums\EnquiryStatus;
use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\Enquiries\Actions\CloseEnquiryAction;
use App\Filament\Resources\Enquiries\Actions\ConvertToServiceUserAction;
use App\Filament\Resources\Enquiries\Actions\LinkToPersonAction;
use App\Filament\Resources\Enquiries\EnquiryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewEnquiry extends ViewRecord
{
    use SyncsPermissionTeamId;

    protected static string $resource = EnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            CloseEnquiryAction::make()
                ->visible(fn (): bool => $this->getRecord()->status === EnquiryStatus::OPEN),
            ConvertToServiceUserAction::make()
                ->visible(fn (): bool => $this->getRecord()->canBeConverted()),
            LinkToPersonAction::make()
                ->visible(fn (): bool => $this->getRecord()->people_id === null && $this->getRecord()->status === EnquiryStatus::OPEN),
        ];
    }
}
