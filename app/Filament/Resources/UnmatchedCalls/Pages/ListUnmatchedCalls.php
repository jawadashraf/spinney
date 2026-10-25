<?php

declare(strict_types=1);

namespace App\Filament\Resources\UnmatchedCalls\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\UnmatchedCalls\UnmatchedCallResource;
use Filament\Resources\Pages\ListRecords;

final class ListUnmatchedCalls extends ListRecords
{
    use SyncsPermissionTeamId;

    protected static string $resource = UnmatchedCallResource::class;

    public function getSubheading(): string
    {
        return 'Calls from numbers that are not on anyone\'s record. Link them to a person or company, start an enquiry, or dismiss them. Calls left unlinked are deleted after '.config('services.yeastar.unmatched_retention_days').' days.';
    }
}
