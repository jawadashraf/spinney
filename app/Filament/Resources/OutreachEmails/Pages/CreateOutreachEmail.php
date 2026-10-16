<?php

declare(strict_types=1);

namespace App\Filament\Resources\OutreachEmails\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\OutreachEmails\OutreachEmailResource;
use Filament\Resources\Pages\CreateRecord;
use Livewire\Attributes\Url;

final class CreateOutreachEmail extends CreateRecord
{
    use SyncsPermissionTeamId;

    protected static string $resource = OutreachEmailResource::class;

    /**
     * Comma-separated company IDs, set by the "Email selected" action on the companies list.
     */
    #[Url]
    public ?string $companies = null;

    protected function afterFill(): void
    {
        if (blank($this->companies)) {
            return;
        }

        $this->data['company_ids'] = collect(explode(',', $this->companies))
            ->map(fn (string $id): int => (int) $id)
            ->filter()
            ->values()
            ->all();
    }

    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
