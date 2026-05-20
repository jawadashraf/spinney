<?php

declare(strict_types=1);

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Schedules\Actions\CancelAppointmentAction;
use App\Filament\Resources\Schedules\Actions\CompleteAppointmentAction;
use App\Filament\Resources\Schedules\Actions\ConfirmAppointmentAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewAppointment extends ViewRecord
{
    use SyncsPermissionTeamId;

    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ConfirmAppointmentAction::make('confirm_appointment'),
            CompleteAppointmentAction::make('complete_appointment'),
            CancelAppointmentAction::make('cancel_appointment'),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
