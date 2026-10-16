<?php

declare(strict_types=1);

namespace App\Filament\Resources\OutreachEmails\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\OutreachEmails\OutreachEmailResource;
use App\Support\MailQuota;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListOutreachEmails extends ListRecords
{
    use SyncsPermissionTeamId;

    protected static string $resource = OutreachEmailResource::class;

    public function getSubheading(): string
    {
        return app(MailQuota::class)->summary();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Write email')
                ->icon('heroicon-o-pencil-square'),
        ];
    }
}
