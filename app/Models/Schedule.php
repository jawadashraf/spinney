<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AttendeeType;
use App\Enums\PaymentType;
use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Zap\Models\Schedule as ZapSchedule;

final class Schedule extends ZapSchedule
{
    use HasTeam;
    /**
     * Get the service user for this appointment.
     *
     * @return BelongsTo<People, $this>
     */
    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(People::class, 'metadata->service_user_id');
    }

    /**
     * Check if this is a service user appointment.
     */
    public function isServiceUserAppointment(): bool
    {
        return ($this->metadata['attendee_type'] ?? null) === AttendeeType::SERVICE_USER->value;
    }

    /**
     * Check if this is a free appointment.
     */
    public function isFreeAppointment(): bool
    {
        return ($this->metadata['payment_type'] ?? null) === PaymentType::FREE->value;
    }

    /**
     * Get the attendee type.
     */
    public function getAttendeeType(): AttendeeType
    {
        return AttendeeType::from($this->metadata['attendee_type'] ?? AttendeeType::SERVICE_USER->value);
    }

    /**
     * Get the payment type.
     */
    public function getPaymentType(): PaymentType
    {
        return PaymentType::from($this->metadata['payment_type'] ?? PaymentType::FREE->value);
    }

    /**
     * Get the external attendee name if applicable.
     */
    public function getExternalAttendeeName(): ?string
    {
        return $this->metadata['external_attendee_name'] ?? null;
    }

    /**
     * Get the external attendee email if applicable.
     */
    public function getExternalAttendeeEmail(): ?string
    {
        return $this->metadata['external_attendee_email'] ?? null;
    }

    /**
     * Get the related care plan ID if applicable.
     */
    public function getCarePlanId(): ?int
    {
        return $this->metadata['care_plan_id'] ?? null;
    }




}
