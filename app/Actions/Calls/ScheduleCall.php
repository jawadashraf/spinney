<?php

declare(strict_types=1);

namespace App\Actions\Calls;

use App\Models\Call;
use App\Models\CallPlan;
use App\Notifications\CallAssignedNotification;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final class ScheduleCall
{
    /**
     * Schedule a call from a plan (regular cadence) or from an existing call (explicit follow-up).
     * Returns null when the call's plan is inactive or has ended by the requested date.
     */
    public function handle(CallPlan|Call $source, CarbonInterface $dueAt, ?Call $parent = null, ?string $reason = null): ?Call
    {
        $plan = $source instanceof CallPlan ? $source : $source->plan;

        if ($plan !== null && ! $plan->coversDate($dueAt)) {
            return null;
        }

        $call = Call::create([
            'team_id' => $source->team_id,
            'call_plan_id' => $plan?->id,
            'people_id' => $source->people_id,
            'assigned_user_id' => $plan->assigned_user_id ?? $source->assigned_user_id,
            'department_id' => $plan->department_id ?? $source->department_id,
            'parent_call_id' => $parent?->id,
            'reason' => $reason ?? ($source instanceof CallPlan ? $source->purpose : $source->reason),
            'due_at' => $dueAt,
            'original_due_at' => $dueAt,
        ]);

        if ($call->assignee !== null && $call->assigned_user_id !== auth()->id()) {
            $call->assignee->notify(new CallAssignedNotification($call));
        }

        return $call;
    }

    /**
     * Book the plan's next regular call after the given call so an active plan always keeps one open call.
     * Returns null when the call has no active plan, the plan already has an open call or the plan has ended.
     */
    public function nextFromPlan(Call $previous): ?Call
    {
        $plan = $previous->plan;

        if ($plan === null || ! $plan->is_active || $plan->calls()->open()->whereKeyNot($previous->getKey())->exists()) {
            return null;
        }

        $dueAt = $plan->nextDueAfter($previous->original_due_at);

        if ($dueAt->isPast()) {
            $dueAt = $plan->nextDueAfter(CarbonImmutable::now()->setTimeFrom($previous->original_due_at));
        }

        return $this->handle($plan, $dueAt, parent: $previous);
    }
}
