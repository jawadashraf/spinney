<?php

declare(strict_types=1);

namespace App\Filament\Resources\MySchedules\Pages;

use App\Filament\Resources\MySchedules\MyScheduleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Zap\Enums\Frequency;

final class EditMySchedule extends EditRecord
{
    protected static string $resource = MyScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (! empty($data['is_recurring']) && isset($data['frequency_config']['days'])) {
            $data['days_of_week'] = $data['frequency_config']['days'];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['is_recurring'])) {
            $data['frequency'] = Frequency::WEEKLY->value;
            $config = is_array($this->record->frequency_config) ? $this->record->frequency_config : (method_exists($this->record->frequency_config, 'toArray') ? $this->record->frequency_config->toArray() : []);
            $config['days'] = $data['days_of_week'] ?? ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            $config['startsOn'] = is_string($data['start_date'] ?? null) ? substr($data['start_date'], 0, 10) : ($config['startsOn'] ?? now()->toDateString());
            $data['frequency_config'] = $config;
        } else {
            $data['frequency'] = null;
            $data['frequency_config'] = null;
        }
        unset($data['days_of_week']);

        if (! (auth()->user()?->hasAnyRole(['admin', 'manager']) ?? false)) {
            $meta = $data['metadata'] ?? [];
            $meta['is_approved'] = false;
            $data['metadata'] = $meta;
        }

        return $data;
    }
}
