<?php

declare(strict_types=1);

namespace App\Filament\Resources\MySchedules\Pages;

use App\Filament\Resources\MySchedules\MyScheduleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditMySchedule extends EditRecord
{
    protected static string $resource = MyScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! (auth()->user()?->hasAnyRole(['admin', 'manager']) ?? false)) {
            $meta = $data['metadata'] ?? [];
            $meta['is_approved'] = false;
            $data['metadata'] = $meta;
        }

        return $data;
    }
}
