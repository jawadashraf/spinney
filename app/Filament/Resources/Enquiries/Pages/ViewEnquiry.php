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
use App\Models\Enquiry;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewEnquiry extends ViewRecord
{
    use SyncsPermissionTeamId;

    protected static string $resource = EnquiryResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Enquiry $enquiry */
        $enquiry = $this->getRecord();

        return [
            EditAction::make(),
            CloseEnquiryAction::make()
                ->visible(fn (): bool => in_array($enquiry->getAttribute('status'), [EnquiryStatus::OPEN->value, EnquiryStatus::IN_PROGRESS->value], true)),
            ConvertToServiceUserAction::make()
                ->visible(fn (): bool => $enquiry->canBeConverted()),
            CreateFollowUpAction::make()
                ->visible(fn (): bool => in_array($enquiry->getAttribute('status'), [EnquiryStatus::OPEN->value, EnquiryStatus::CLOSED->value, EnquiryStatus::IN_PROGRESS->value], true)),
            AssignToDepartmentAction::make()
                ->visible(fn (): bool => in_array($enquiry->getAttribute('status'), [EnquiryStatus::OPEN->value, EnquiryStatus::IN_PROGRESS->value], true)),
            LinkToPersonAction::make()
                ->visible(fn (): bool => $enquiry->people_id === null && in_array($enquiry->getAttribute('status'), [EnquiryStatus::OPEN->value, EnquiryStatus::IN_PROGRESS->value], true)),
        ];
    }
}
