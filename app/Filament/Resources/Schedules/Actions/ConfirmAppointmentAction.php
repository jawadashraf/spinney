<?php

declare(strict_types=1);

namespace App\Filament\Resources\Schedules\Actions;

use App\Enums\AppointmentStatus;
use App\Models\Schedule;
use Filament\Actions\Action;

final class ConfirmAppointmentAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Confirm')
            ->icon('heroicon-o-check-circle')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('Confirm Appointment')
            ->modalDescription('Mark this appointment as confirmed.')
            ->visible(fn (Schedule $record): bool => ($record->metadata['appointment_status'] ?? AppointmentStatus::SCHEDULED->value) === AppointmentStatus::SCHEDULED->value
                && ($record->schedule_type->value ?? '') === 'appointment')
            ->action(function (Schedule $record): void {
                $record->metadata = array_merge($record->metadata ?? [], [
                    'appointment_status' => AppointmentStatus::CONFIRMED->value,
                ]);
                $record->save();
            });
    }
}
