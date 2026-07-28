<?php

declare(strict_types=1);

namespace App\Filament\Resources\MySchedules\Pages;

use App\Filament\Resources\MySchedules\MyScheduleResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Zap\Enums\Frequency;

final class CreateMySchedule extends CreateRecord
{
    protected static string $resource = MyScheduleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['schedulable_type'] = (new User)->getMorphClass();
        $data['schedulable_id'] = auth()->id();

        if (! empty($data['is_recurring'])) {
            $data['frequency'] = Frequency::WEEKLY->value;
            $data['frequency_config'] = [
                'days' => $data['days_of_week'] ?? ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'startsOn' => is_string($data['start_date'] ?? null) ? substr($data['start_date'], 0, 10) : now()->toDateString(),
            ];
        }
        unset($data['days_of_week']);

        $meta = $data['metadata'] ?? [];
        if (! (auth()->user()?->hasAnyRole(['admin', 'manager']) ?? false)) {
            $meta['is_approved'] = false;
        } else {
            $meta['is_approved'] = true;
        }
        $data['metadata'] = $meta;

        return $data;
    }
}
