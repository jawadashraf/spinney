<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\AttendanceStatus;
use App\Enums\AttendeeType;
use App\Enums\CounselorType;
use App\Enums\PaymentType;
use App\Enums\SessionType;
use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Zap\Models\Schedule as ZapSchedule;

#[Fillable([
    'team_id',
    'schedulable_type',
    'schedulable_id',
    'name',
    'description',
    'schedule_type',
    'start_date',
    'end_date',
    'is_recurring',
    'frequency',
    'frequency_config',
    'metadata',
    'is_active',
])]
final class Schedule extends ZapSchedule
{
    use HasTeam;

    //    protected static function booted(): void
    //    {
    //        self::creating(function (self $schedule): void {
    //            if (auth()->check()) {
    //                $user = auth()->user();
    //                $schedule->team_id = $user->currentTeam?->getKey();
    //            }
    //            if ($schedule->isDirty('metadata') && is_array($schedule->metadata)) {
    //                $schedule->metadata = array_filter($schedule->metadata, fn ($value): bool => $value !== null && $value !== '');
    //            }
    //        });
    //
    //        self::updating(function (self $schedule): void {
    //            if ($schedule->isDirty('metadata') && is_array($schedule->metadata)) {
    //                $schedule->metadata = array_filter($schedule->metadata, fn ($value): bool => $value !== null && $value !== '');
    //            }
    //        });
    //    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function schedulable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<People, $this>
     */
    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(People::class, 'metadata->service_user_id');
    }

    /**
     * Appointment statuses that still await the session.
     *
     * @var list<AppointmentStatus>
     */
    public const array OPEN_STATUSES = [AppointmentStatus::SCHEDULED, AppointmentStatus::CONFIRMED];

    /**
     * Limit a schedule query to appointments with any of the given statuses. Appointments booked before the
     * status was recorded have none, and count as scheduled.
     *
     * @template TQuery of Builder
     *
     * @param  TQuery  $query
     * @param  list<AppointmentStatus>  $statuses
     * @return TQuery
     */
    public static function whereAppointmentStatus(Builder $query, array $statuses): Builder
    {
        $values = array_map(fn (AppointmentStatus $status): string => $status->value, $statuses);

        return $query->where(function (Builder $query) use ($values, $statuses): void {
            $query->whereIn('metadata->appointment_status', $values);

            if (in_array(AppointmentStatus::SCHEDULED, $statuses, true)) {
                $query->orWhereNull('metadata->appointment_status');
            }
        });
    }

    /**
     * The staff member the appointment is with, when it is a user.
     */
    public function counsellor(): ?User
    {
        return $this->schedulable instanceof User ? $this->schedulable : null;
    }

    public function isWithCounsellor(User $user): bool
    {
        return $this->schedulable_type === $user->getMorphClass() && (int) $this->schedulable_id === (int) $user->getKey();
    }

    /**
     * Session notes are for the counsellor who held the session, managers and admins only.
     */
    public function sessionNotesVisibleTo(?User $user): bool
    {
        return $user instanceof User && ($user->managesAppointments() || $this->isWithCounsellor($user));
    }

    /**
     * The booked service user. The serviceUser relation's key is a JSON path, which Eloquent cannot lazy-load
     * for a single model, so it is looked up directly.
     */
    public function serviceUserRecord(): ?People
    {
        $id = $this->metadata['service_user_id'] ?? null;

        return $id === null ? null : People::query()->find((int) $id);
    }

    public function attendeeName(): string
    {
        return $this->isServiceUserAppointment()
            ? ($this->serviceUserRecord()->name ?? 'Service user')
            : ($this->getExternalAttendeeName() ?? 'Attendee');
    }

    public function getAttendance(): ?AttendanceStatus
    {
        return AttendanceStatus::tryFrom((string) ($this->metadata['attendance'] ?? ''));
    }

    /**
     * Start and end time, e.g. "10:00 - 11:00", from the booked slot, else the first period, else the dates.
     */
    public function timeRange(): string
    {
        $start = $this->metadata['start_time'] ?? $this->periods->first()->start_time ?? null;
        $end = $this->metadata['end_time'] ?? $this->periods->first()->end_time ?? null;

        if ($start === null || $end === null) {
            $start = $this->start_date->format('H:i');
            $end = $this->end_date?->format('H:i');
        }

        return substr((string) $start, 0, 5).' - '.substr((string) $end, 0, 5);
    }

    public function startsAt(): Carbon
    {
        $time = substr((string) ($this->metadata['start_time'] ?? $this->periods->first()->start_time ?? '00:00'), 0, 5);

        return Carbon::parse($this->start_date->format('Y-m-d').' '.$time);
    }

    public function isServiceUserAppointment(): bool
    {
        return ($this->metadata['attendee_type'] ?? null) === AttendeeType::SERVICE_USER->value;
    }

    public function isFreeAppointment(): bool
    {
        return ($this->metadata['payment_type'] ?? null) === PaymentType::FREE->value;
    }

    public function getAttendeeType(): AttendeeType
    {
        return AttendeeType::from($this->metadata['attendee_type'] ?? AttendeeType::SERVICE_USER->value);
    }

    public function getPaymentType(): PaymentType
    {
        return PaymentType::from($this->metadata['payment_type'] ?? PaymentType::FREE->value);
    }

    public function getExternalAttendeeName(): ?string
    {
        return $this->metadata['external_attendee_name'] ?? null;
    }

    public function getExternalAttendeeEmail(): ?string
    {
        return $this->metadata['external_attendee_email'] ?? null;
    }

    public function getCarePlanId(): ?int
    {
        return $this->metadata['care_plan_id'] ?? null;
    }

    public function getAppointmentStatus(): AppointmentStatus
    {
        return AppointmentStatus::from($this->metadata['appointment_status'] ?? AppointmentStatus::SCHEDULED->value);
    }

    public function getCounselorType(): ?CounselorType
    {
        $value = $this->metadata['counselor_type'] ?? null;

        return $value ? CounselorType::tryFrom($value) : null;
    }

    public function getSessionType(): ?SessionType
    {
        $value = $this->metadata['session_type'] ?? null;

        return $value ? SessionType::tryFrom($value) : null;
    }

    public function getSlotDurationMinutes(): int
    {
        return (int) ($this->metadata['slot_duration_minutes'] ?? 60);
    }

    public function getCapacity(): int
    {
        return (int) ($this->metadata['capacity'] ?? 1);
    }

    public function isLocked(): bool
    {
        return ($this->metadata['is_locked'] ?? false) === true;
    }

    public function getBlockedReason(): ?string
    {
        return $this->metadata['reason'] ?? null;
    }
}
