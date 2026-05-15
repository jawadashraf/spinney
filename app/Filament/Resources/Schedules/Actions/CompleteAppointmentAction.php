<?php

declare(strict_types=1);

namespace App\Filament\Resources\Schedules\Actions;

use App\Enums\AppointmentStatus;
use App\Models\Schedule;
use Filament\Actions\Action;

final class CompleteAppointmentAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Complete')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Complete Appointment')
            ->modalDescription('Mark this appointment as completed.')
            ->visible(fn (Schedule $record): bool => ($record->metadata['appointment_status'] ?? AppointmentStatus::SCHEDULED->value) === AppointmentStatus::CONFIRMED->value
                && ($record->schedule_type->value ?? '') === 'appointment')
            ->action(function (Schedule $record): void {
                $record->metadata = array_merge($record->metadata ?? [], [
                    'appointment_status' => AppointmentStatus::COMPLETED->value,
                ]);
                $record->save();
            });
    }
}
