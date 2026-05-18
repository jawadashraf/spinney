<?php

declare(strict_types=1);

namespace App\Filament\Resources\Schedules\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\Schedules\Actions\CancelAppointmentAction;
use App\Filament\Resources\Schedules\Actions\CompleteAppointmentAction;
use App\Filament\Resources\Schedules\Actions\ConfirmAppointmentAction;
use App\Filament\Resources\Schedules\Actions\LockAvailabilityAction;
use App\Filament\Resources\Schedules\Actions\UnlockAvailabilityAction;
use App\Filament\Resources\Schedules\ScheduleResource;
use App\Filament\Resources\Schedules\Schemas\ScheduleForm;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditSchedule extends EditRecord
{
    use SyncsPermissionTeamId;

    protected static string $resource = ScheduleResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return array_merge($data, ScheduleForm::fillFormFromRecord($this->record));
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return ScheduleForm::mutateFormDataBeforeSave($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            LockAvailabilityAction::make('lock_availability'),
            UnlockAvailabilityAction::make('unlock_availability'),
            ConfirmAppointmentAction::make('confirm_appointment'),
            CompleteAppointmentAction::make('complete_appointment'),
            CancelAppointmentAction::make('cancel_appointment'),
            DeleteAction::make(),
        ];
    }
}
