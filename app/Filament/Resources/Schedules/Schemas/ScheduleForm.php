<?php

declare(strict_types=1);

namespace App\Filament\Resources\Schedules\Schemas;

use Filament\Schemas\Schema;

final class ScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}
