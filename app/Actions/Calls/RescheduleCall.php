<?php

declare(strict_types=1);

namespace App\Actions\Calls;

use App\Models\Call;
use Carbon\CarbonInterface;
use Spatie\Activitylog\Models\Activity;

final class RescheduleCall
{
    public function handle(Call $call, CarbonInterface $dueAt, ?string $reason = null): void
    {
        $previousDueAt = $call->due_at;

        $call->update(['due_at' => $dueAt]);

        activity()
            ->performedOn($call)
            ->causedBy(auth()->user())
            ->withProperties([
                'from' => $previousDueAt->toDateTimeString(),
                'to' => $call->due_at->toDateTimeString(),
                'reason' => $reason,
            ])
            ->tap(fn (Activity $activity) => $activity->setAttribute('team_id', $call->team_id))
            ->log('Call rescheduled');
    }
}
