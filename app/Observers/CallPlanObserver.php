<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\Calls\ScheduleCall;
use App\Enums\CallStatus;
use App\Models\CallPlan;
use Carbon\CarbonImmutable;

final readonly class CallPlanObserver
{
    public function __construct(private ScheduleCall $scheduleCall) {}

    public function created(CallPlan $plan): void
    {
        if ($plan->is_active) {
            $this->scheduleCall->handle($plan, $plan->firstDueAt());
        }
    }

    public function updated(CallPlan $plan): void
    {
        if ($plan->wasChanged('is_active')) {
            if (! $plan->is_active) {
                $plan->calls()->open()->update(['status' => CallStatus::Cancelled]);
            } elseif (! $plan->calls()->open()->exists()) {
                $firstDueAt = $plan->firstDueAt();
                $this->scheduleCall->handle($plan, $firstDueAt->isPast() ? CarbonImmutable::now()->addDay()->setTimeFrom($firstDueAt) : $firstDueAt);
            }
        }

        if ($plan->wasChanged('assigned_user_id')) {
            $plan->calls()->open()->update(['assigned_user_id' => $plan->assigned_user_id]);
        }
    }
}
