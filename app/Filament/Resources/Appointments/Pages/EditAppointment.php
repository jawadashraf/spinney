<?php

declare(strict_types=1);

namespace App\Filament\Resources\Appointments\Pages;

use App\Enums\SessionType;
use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Schedules\Actions\CancelAppointmentAction;
use App\Filament\Resources\Schedules\Actions\CompleteAppointmentAction;
use App\Filament\Resources\Schedules\Actions\ConfirmAppointmentAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Zap\Models\Schedule;

final class EditAppointment extends EditRecord
{
    use SyncsPermissionTeamId;

    protected static string $resource = AppointmentResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['metadata']['session_type'] ??= SessionType::INDIVIDUAL->value;

        return $data;
    }

    /**
     * Only the appointment's details change here. Its slot, status and session record are kept: the form only
     * holds some metadata keys, so it is merged into what the appointment already has.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Schedule $record */
        $record = $this->record;

        $data['metadata'] = [...($record->metadata ?? []), ...($data['metadata'] ?? [])];

        return $data;
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
