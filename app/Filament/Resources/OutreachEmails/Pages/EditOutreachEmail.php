<?php

declare(strict_types=1);

namespace App\Filament\Resources\OutreachEmails\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\OutreachEmails\OutreachEmailResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditOutreachEmail extends EditRecord
{
    use SyncsPermissionTeamId;

    protected static string $resource = OutreachEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
