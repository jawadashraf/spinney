<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceUsers\Pages;

use App\Filament\Resources\ServiceUsers\Concerns\HasServiceUserTabNavigation;
use App\Filament\Resources\ServiceUsers\ServiceUserResource;
use App\Models\ServiceUser;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * Read-only service user record, with the same tabs as the edit page. Used by staff who may read a record but
 * not change it, such as counsellors opening their own clients.
 */
final class ViewServiceUser extends ViewRecord
{
    use HasServiceUserTabNavigation;

    protected static string $resource = ServiceUserResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): string
    {
        return 'Details & Profile';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var ServiceUser $record */
        $record = $this->record;
        $profile = $record->profile;

        if ($profile) {
            $data['profile'] = $profile->toArray();
        }

        return $data;
    }
}
