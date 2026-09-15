<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\Calls\ScheduleCall;
use App\Enums\CallStatus;
use App\Models\CallPlan;
use App\Notifications\CallAssignedNotification;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

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
            $this->moveOpenCallsToAssignee($plan);
        }
    }

    /**
     * Give the plan's open calls to its new liaison and notify them about the calls that were not already theirs.
     */
    private function moveOpenCallsToAssignee(CallPlan $plan): void
    {
        $assignee = $plan->assignee()->first();

        $movedCalls = $assignee === null
            ? collect()
            : $plan->calls()
                ->open()
                ->where(fn (Builder $query): Builder => $query
                    ->whereNull('calls.assigned_user_id')
                    ->orWhere('calls.assigned_user_id', '!=', $assignee->id))
                ->orderBy('calls.due_at')
                ->get();

        $plan->calls()->open()->update(['assigned_user_id' => $plan->assigned_user_id]);

        if ($assignee !== null && $movedCalls->isNotEmpty() && $assignee->id !== auth()->id()) {
            $assignee->notify(new CallAssignedNotification($movedCalls->first(), $movedCalls->count()));
        }
    }
}
