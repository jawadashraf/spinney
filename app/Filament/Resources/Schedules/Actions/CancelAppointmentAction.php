<?php

declare(strict_types=1);

namespace App\Filament\Resources\Schedules\Actions;

use App\Enums\AppointmentStatus;
use App\Models\Schedule;
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
            ->modalDescription('Cancel this appointment. A reason is recommended.')
            ->form([
                Textarea::make('cancellation_reason')
                    ->label('Cancellation Reason')
                    ->maxLength(500)
                    ->rows(3),
            ])
            ->visible(fn (Schedule $record): bool => in_array(
                $record->metadata['appointment_status'] ?? AppointmentStatus::SCHEDULED->value,
                [AppointmentStatus::SCHEDULED->value, AppointmentStatus::CONFIRMED->value]
            ) && ($record->schedule_type->value ?? '') === 'appointment')
            ->action(function (Schedule $record, array $data): void {
                $record->metadata = array_merge($record->metadata ?? [], [
                    'appointment_status' => AppointmentStatus::CANCELLED->value,
                    'cancellation_reason' => $data['cancellation_reason'] ?? null,
                    'cancelled_at' => now()->toIso8601String(),
                    'cancelled_by' => auth()->id(),
                ]);
                $record->is_active = false;
                $record->save();
            });
    }
}
