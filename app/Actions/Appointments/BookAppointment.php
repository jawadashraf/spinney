<?php

declare(strict_types=1);

namespace App\Actions\Appointments;

use App\Enums\AppointmentStatus;
use App\Models\Schedule;
use App\Notifications\AppointmentBookedNotification;
use App\Support\AppointmentRecipients;
use Illuminate\Database\Eloquent\Model;
use Zap\Exceptions\InvalidScheduleException;
use Zap\Exceptions\ScheduleConflictException;
use Zap\Facades\Zap;

/**
 * Books a one-off appointment with a counsellor (or other schedulable) for one time slot, then tells the
 * counsellor and the attendee. Booking through Zap records the slot as a period, so it is no longer offered
 * as available.
 */
final readonly class BookAppointment
{
    /**
     * @param  array<string, mixed>  $metadata  attendee_type, service_user_id or external_attendee_*, counselor_type, session_type, payment_type…
     *
     * @throws ScheduleConflictException
     * @throws InvalidScheduleException
     */
    public function handle(
        Model $schedulable,
        string $date,
        string $startTime,
        string $endTime,
        string $name,
        array $metadata,
        int $teamId,
        ?string $description = null,
        bool $active = true,
    ): Schedule {
        $metadata = array_filter([
            ...$metadata,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'appointment_status' => AppointmentStatus::SCHEDULED->value,
        ], fn (mixed $value): bool => $value !== null && $value !== '');

        $builder = Zap::for($schedulable)
            ->named($name)
            ->from($date)
            ->appointment()
            ->addPeriod($startTime, $endTime)
            ->withMetadata($metadata);

        if (filled($description)) {
            $builder->description($description);
        }

        $active ? $builder->active() : $builder->inactive();

        /** @var Schedule $appointment */
        $appointment = $builder->save();
        $appointment->forceFill(['team_id' => $teamId])->save();

        AppointmentRecipients::notify($appointment, new AppointmentBookedNotification($appointment));

        return $appointment;
    }
}
