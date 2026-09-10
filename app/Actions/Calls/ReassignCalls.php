<?php

declare(strict_types=1);

namespace App\Actions\Calls;

use App\Models\Call;
use App\Models\CallPlan;
use App\Models\User;
use App\Notifications\CallAssignedNotification;

final class ReassignCalls
{
    /**
     * Move open calls (and optionally their plans) to another liaison.
     *
     * @param  iterable<Call>  $calls
     */
    public function handle(iterable $calls, User $to, bool $includePlans = false): int
    {
        $reassigned = collect();
        $plans = collect();

        foreach ($calls as $call) {
            if (! $call->isOpen()) {
                continue;
            }

            $call->update(['assigned_user_id' => $to->id]);
            $reassigned->push($call);

            if ($includePlans && $call->plan !== null) {
                $plans->put($call->plan->id, $call->plan);
            }
        }

        $plans->each(fn (CallPlan $plan) => $plan->update(['assigned_user_id' => $to->id]));

        if ($reassigned->isNotEmpty() && $to->id !== auth()->id()) {
            $to->notify(new CallAssignedNotification($reassigned->first(), $reassigned->count()));
        }

        return $reassigned->count();
    }
}
