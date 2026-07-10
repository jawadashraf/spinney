<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enquiries\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Filament\Resources\Enquiries\Schemas\EnquiryForm;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Wizard\Step;

final class CreateEnquiry extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;
    use SyncsPermissionTeamId;

    protected static string $resource = EnquiryResource::class;

    public function hasSkippableSteps(): bool
    {
        return true;
    }

    /**
     * @return array<int, Step>
     */
    protected function getSteps(): array
    {
        return EnquiryForm::getWizardSteps();
    }
}
