<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\AttendeeType;
use App\Enums\CounselorType;
use App\Enums\PaymentType;
use App\Enums\SessionType;
use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Zap\Models\Schedule as ZapSchedule;

final class Schedule extends ZapSchedule
{
    use HasTeam;

    protected $fillable = [
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
    ];

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

    public function schedulable(): MorphTo
    {
        return $this->morphTo();
    }

    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(People::class, 'metadata->service_user_id');
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
