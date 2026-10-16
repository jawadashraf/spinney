<?php

declare(strict_types=1);

namespace App\Actions\Appointments;

use App\Enums\AppointmentStatus;
use App\Enums\AttendanceStatus;
use App\Models\Schedule;
use App\Models\User;
use App\Notifications\AppointmentCancelledNotification;
use App\Support\AppointmentRecipients;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

/**
 * Moves an appointment through scheduled → confirmed → completed, or to cancelled, recording each change in
 * the audit log (AC AP-005). Session notes are stored on the appointment but never copied into the audit log,
 * which more staff can read.
 */
final readonly class UpdateAppointmentStatus
{
    public function confirm(Schedule $appointment, User $by): void
    {
        $this->transition($appointment, $by, AppointmentStatus::CONFIRMED, [], 'Appointment confirmed');
    }

    public function cancel(Schedule $appointment, User $by, ?string $reason): void
    {
        $this->transition($appointment, $by, AppointmentStatus::CANCELLED, [
            'cancellation_reason' => $reason,
            'cancelled_at' => now()->toIso8601String(),
            'cancelled_by' => $by->getKey(),
        ], 'Appointment cancelled', ['reason' => $reason]);

        $appointment->forceFill(['is_active' => false])->save();

        AppointmentRecipients::notify($appointment, new AppointmentCancelledNotification($appointment));
    }

    /**
     * Record how the session went. A no-show still completes the appointment.
     */
    public function complete(Schedule $appointment, User $by, AttendanceStatus $attendance, ?string $sessionNote, ?string $nextStep): void
    {
        if (! in_array($attendance, [AttendanceStatus::ATTENDED, AttendanceStatus::NO_SHOW], true)) {
            throw ValidationException::withMessages(['attendance' => 'Choose whether the session was attended.']);
        }

        $this->transition($appointment, $by, AppointmentStatus::COMPLETED, [
            'attendance' => $attendance->value,
            'session_note' => $sessionNote,
            'next_step' => $nextStep,
            'completed_at' => now()->toIso8601String(),
            'completed_by' => $by->getKey(),
        ], $attendance === AttendanceStatus::ATTENDED ? 'Session completed: attended' : 'Session completed: no-show', [
            'attendance' => $attendance->value,
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $properties
     */
    private function transition(Schedule $appointment, User $by, AppointmentStatus $to, array $metadata, string $description, array $properties = []): void
    {
        $from = $appointment->getAppointmentStatus();

        if (! $from->canTransitionTo($to)) {
            throw ValidationException::withMessages([
                'status' => "A {$from->getLabel()} appointment cannot be marked {$to->getLabel()}.",
            ]);
        }

        $appointment->metadata = array_merge($appointment->metadata ?? [], $metadata, ['appointment_status' => $to->value]);
        $appointment->save();

        activity()
            ->performedOn($appointment)
            ->causedBy($by)
            ->withProperties(['from' => $from->value, 'to' => $to->value, ...$properties])
            ->tap(fn (Activity $activity) => $activity->setAttribute('team_id', $appointment->team_id))
            ->log($description);
    }
}
