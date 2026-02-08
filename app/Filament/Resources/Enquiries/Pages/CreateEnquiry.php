<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enquiries\Pages;

use App\Filament\Resources\Enquiries\EnquiryResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateEnquiry extends CreateRecord
{
    protected static string $resource = EnquiryResource::class;
}
