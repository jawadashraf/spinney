<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enquiries\Pages;

use App\Enums\EnquiryStatus;
use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\Enquiries\Actions\AssignToDepartmentAction;
use App\Filament\Resources\Enquiries\Actions\CloseEnquiryAction;
use App\Filament\Resources\Enquiries\Actions\ConvertToServiceUserAction;
use App\Filament\Resources\Enquiries\Actions\CreateFollowUpAction;
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
                ->visible(fn (): bool => in_array($this->getRecord()->status, [EnquiryStatus::OPEN, EnquiryStatus::IN_PROGRESS], true)),
            ConvertToServiceUserAction::make()
                ->visible(fn (): bool => $this->getRecord()->canBeConverted()),
            CreateFollowUpAction::make()
                ->visible(fn (): bool => in_array($this->getRecord()->status, [EnquiryStatus::OPEN, EnquiryStatus::CLOSED, EnquiryStatus::IN_PROGRESS], true)),
            AssignToDepartmentAction::make()
                ->visible(fn (): bool => in_array($this->getRecord()->status, [EnquiryStatus::OPEN, EnquiryStatus::IN_PROGRESS], true)),
            LinkToPersonAction::make()
                ->visible(fn (): bool => $this->getRecord()->people_id === null && in_array($this->getRecord()->status, [EnquiryStatus::OPEN, EnquiryStatus::IN_PROGRESS], true)),
        ];
    }
}
