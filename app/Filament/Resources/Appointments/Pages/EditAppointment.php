<?php

declare(strict_types=1);

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Appointments\Schemas\AppointmentForm;
use App\Filament\Resources\Schedules\Actions\CancelAppointmentAction;
use App\Filament\Resources\Schedules\Actions\CompleteAppointmentAction;
use App\Filament\Resources\Schedules\Actions\ConfirmAppointmentAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditAppointment extends EditRecord
{
    use SyncsPermissionTeamId;

    protected static string $resource = AppointmentResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return array_merge($data, AppointmentForm::fillFormFromRecord($this->record));
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return AppointmentForm::mutateFormDataBeforeSave($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            ConfirmAppointmentAction::make('confirm_appointment'),
            CompleteAppointmentAction::make('complete_appointment'),
            CancelAppointmentAction::make('cancel_appointment'),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
