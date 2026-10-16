<?php

declare(strict_types=1);

namespace App\Filament\Resources\Schedules\Actions;

use App\Actions\Appointments\UpdateAppointmentStatus;
use App\Enums\AppointmentStatus;
use App\Models\Schedule;
use App\Models\User;
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
            ->modalDescription('Mark this appointment as confirmed with the attendee.')
            ->visible(fn (Schedule $record): bool => ($record->schedule_type->value ?? '') === 'appointment'
                && $record->getAppointmentStatus()->canTransitionTo(AppointmentStatus::CONFIRMED))
            ->action(function (Schedule $record, UpdateAppointmentStatus $update): void {
                /** @var User $user */
                $user = auth()->user();
                $update->confirm($record, $user);
            })
            ->successNotificationTitle('Appointment confirmed');
    }
}
