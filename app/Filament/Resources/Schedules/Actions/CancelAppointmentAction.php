<?php

declare(strict_types=1);

namespace App\Filament\Resources\Schedules\Actions;

use App\Actions\Appointments\UpdateAppointmentStatus;
use App\Enums\AppointmentStatus;
use App\Models\Schedule;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;

final class CancelAppointmentAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Cancel')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Cancel Appointment')
            ->modalDescription('The counsellor and the attendee will be emailed. The reason is only shared with staff.')
            ->schema([
                Textarea::make('cancellation_reason')
                    ->label('Cancellation Reason')
                    ->maxLength(500)
                    ->rows(3),
            ])
            ->visible(fn (Schedule $record): bool => ($record->schedule_type->value ?? '') === 'appointment'
                && $record->getAppointmentStatus()->canTransitionTo(AppointmentStatus::CANCELLED))
            ->action(function (Schedule $record, array $data, UpdateAppointmentStatus $update): void {
                /** @var User $user */
                $user = auth()->user();
                $update->cancel($record, $user, $data['cancellation_reason'] ?? null);
            })
            ->successNotificationTitle('Appointment cancelled');
    }
}
