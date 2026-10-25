<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enquiries\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Filament\Resources\Enquiries\Schemas\EnquiryForm;
use App\Filament\Resources\UnmatchedCalls\UnmatchedCallResource;
use App\Models\PbxCallLog;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Wizard\Step;
use Livewire\Attributes\Url;

final class CreateEnquiry extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;
    use SyncsPermissionTeamId;

    /**
     * An unmatched phone system call the enquiry is started from (Unmatched calls → Start enquiry).
     */
    #[Url(as: 'pbx_call')]
    public ?int $pbxCallId = null;

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

    protected function afterFill(): void
    {
        $call = $this->phoneSystemCall();

        if ($call === null) {
            return;
        }

        $this->data = [
            ...$this->data,
            'caller_identification_mode' => 'new',
            'phone' => $call->contact_number,
            'direction' => $call->direction,
            'source' => 'phone',
            'occurred_at' => $call->started_at->toDateTimeString(),
        ];
    }

    protected function afterCreate(): void
    {
        $this->phoneSystemCall()?->update(['enquiry_id' => $this->getRecord()->getKey()]);
    }

    private function phoneSystemCall(): ?PbxCallLog
    {
        if ($this->pbxCallId === null) {
            return null;
        }

        // Only an unmatched call this user may see in Unmatched calls.
        return UnmatchedCallResource::getEloquentQuery()->find($this->pbxCallId);
    }
}
