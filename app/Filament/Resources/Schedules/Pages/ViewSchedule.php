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
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewSchedule extends ViewRecord
{
    use SyncsPermissionTeamId;

    protected static string $resource = ScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LockAvailabilityAction::make('lock_availability'),
            UnlockAvailabilityAction::make('unlock_availability'),
            ConfirmAppointmentAction::make('confirm_appointment'),
            CompleteAppointmentAction::make('complete_appointment'),
            CancelAppointmentAction::make('cancel_appointment'),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
