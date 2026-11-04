<?php

declare(strict_types=1);

namespace App\Filament\Resources\PhoneCalls\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\PhoneCalls\PhoneCallResource;
use App\Models\Call;
use App\Models\User;
use Filament\Resources\Pages\ListRecords;

final class ListPhoneCalls extends ListRecords
{
    use SyncsPermissionTeamId;

    protected static string $resource = PhoneCallResource::class;

    public function getSubheading(): string
    {
        $user = auth()->user();

        return $user instanceof User && Call::userSeesAllCalls($user)
            ? 'Every call made or received on the phone system.'
            : 'Calls you made or received on the phone system. Calls from an extension that is not linked to you are not shown.';
    }
}
