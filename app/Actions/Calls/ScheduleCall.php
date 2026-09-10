<?php

declare(strict_types=1);

namespace App\Actions\Calls;

use App\Models\Call;
use App\Models\CallPlan;
use App\Notifications\CallAssignedNotification;
use Carbon\CarbonInterface;

final class ScheduleCall
{
    /**
     * Schedule a call from a plan (regular cadence) or from an existing call (explicit follow-up).
     * Returns null when a plan no longer covers the requested date.
     */
    public function handle(CallPlan|Call $source, CarbonInterface $dueAt, ?Call $parent = null, ?string $reason = null): ?Call
    {
        $plan = $source instanceof CallPlan ? $source : $source->plan;

        if ($source instanceof CallPlan && ! $source->coversDate($dueAt)) {
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
}
