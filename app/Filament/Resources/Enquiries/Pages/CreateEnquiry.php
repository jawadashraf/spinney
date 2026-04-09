<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enquiries\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\Enquiries\EnquiryResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateEnquiry extends CreateRecord
{
    use SyncsPermissionTeamId;

    protected static string $resource = EnquiryResource::class;
}
