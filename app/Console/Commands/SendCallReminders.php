<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Call;
use App\Models\CallPlan;
use App\Models\User;
use App\Notifications\CallPlansWithoutCallsNotification;
use App\Notifications\CallsDueDigestNotification;
use App\Notifications\CallsOverdueDigestNotification;
use App\Support\TeamManagers;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

#[Signature('calls:send-reminders')]
#[Description('Send liaisons a digest of calls due today/overdue and managers a summary of long-overdue calls and call plans with no call booked')]
final class SendCallReminders extends Command
{
    public const int MANAGER_ESCALATION_DAYS = 2;

    public function handle(): int
    {
        $liaisonDigests = $this->notifyLiaisons();
        $managerDigests = $this->notifyManagers();
        $planAlerts = $this->notifyManagersOfPlansWithoutCalls();

        $this->info("Sent {$liaisonDigests} liaison digest(s), {$managerDigests} manager digest(s) and {$planAlerts} call plan alert(s).");

        return self::SUCCESS;
    }

    private function notifyLiaisons(): int
    {
        $calls = Call::query()
            ->open()
            ->whereNotNull('assigned_user_id')
            ->where('due_at', '<=', today()->endOfDay())
            ->with(['assignee', 'team'])
            ->get();

        $calls
            ->groupBy(fn (Call $call): string => $call->team_id.'-'.$call->assigned_user_id)
            ->each(function (Collection $assigneeCalls): void {
                /** @var Call $first */
                $first = $assigneeCalls->first();

                $overdue = $assigneeCalls->filter(fn (Call $call): bool => $call->due_at->lt(today()))->count();

                $first->assignee?->notify(new CallsDueDigestNotification(
                    $first->team,
                    $assigneeCalls->count() - $overdue,
                    $overdue,
                ));
            });

        return $calls->pluck('assigned_user_id')->unique()->count();
    }

    private function notifyManagers(): int
    {
        $sent = 0;

        Call::query()
            ->open()
            ->where('due_at', '<', now()->subDays(self::MANAGER_ESCALATION_DAYS))
            ->with(['assignee', 'team'])
            ->get()
            ->groupBy('team_id')
            ->each(function (Collection $teamCalls, int|string $teamId) use (&$sent): void {
                $overdueByLiaison = $teamCalls
                    ->groupBy(fn (Call $call): string => $call->assignee->name ?? 'Unassigned')
                    ->map(fn (Collection $calls): int => $calls->count())
                    ->all();

                TeamManagers::for((int) $teamId)->each(function ($manager) use ($teamCalls, $overdueByLiaison, &$sent): void {
                    $manager->notify(new CallsOverdueDigestNotification($teamCalls->first()->team, $overdueByLiaison));
                    $sent++;
                });
            });

        return $sent;
    }

    /**
     * Alert managers to active, unfinished call plans that have no open call, so nobody silently stops being called.
     */
    private function notifyManagersOfPlansWithoutCalls(): int
    {
        $sent = 0;

        CallPlan::query()
            ->where('is_active', true)
            ->where(fn (Builder $query): Builder => $query->whereNull('ends_on')->orWhere('ends_on', '>=', today()))
            ->whereDoesntHave('calls', fn (Builder $query): Builder => $query->open())
            ->with(['serviceUser', 'team'])
            ->get()
            ->groupBy('team_id')
            ->each(function (Collection $teamPlans, int|string $teamId) use (&$sent): void {
                $serviceUserNames = $teamPlans
                    ->map(fn (CallPlan $plan): string => $plan->serviceUser->name ?? 'Unknown service user')
                    ->sort()
                    ->values()
                    ->all();

                TeamManagers::for((int) $teamId)->each(function (User $manager) use ($teamPlans, $serviceUserNames, &$sent): void {
                    $manager->notify(new CallPlansWithoutCallsNotification($teamPlans->first()->team, $serviceUserNames));
                    $sent++;
                });
            });

        return $sent;
    }
}
