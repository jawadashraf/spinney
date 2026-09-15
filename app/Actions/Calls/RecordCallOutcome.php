<?php

declare(strict_types=1);

namespace App\Actions\Calls;

use App\Enums\CallOutcome;
use App\Enums\CallStatus;
use App\Enums\CreationSource;
use App\Enums\SupportStatus;
use App\Models\Call;
use App\Models\Note;
use App\Models\People;
use App\Models\User;
use App\Notifications\CallAttemptsExhaustedNotification;
use App\Support\ServiceUserSupportStatus;
use App\Support\TeamManagers;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final readonly class RecordCallOutcome
{
    public function __construct(private ScheduleCall $scheduleCall) {}

    /**
     * Keys: outcome (required), notes, action_required, next_follow_up_at, retry_at, close_call, raise_concern, support_status.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Call $call, User $by, array $data): Call
    {
        return DB::transaction(function () use ($call, $by, $data): Call {
            $outcome = $data['outcome'] instanceof CallOutcome ? $data['outcome'] : CallOutcome::from((string) $data['outcome']);
            $raiseConcern = (bool) ($data['raise_concern'] ?? false);
            $supportStatus = $raiseConcern ? SupportStatus::from((string) ($data['support_status'] ?? SupportStatus::NeedsAttention->value)) : null;

            $call->attempts()->create([
                'team_id' => $call->team_id,
                'user_id' => $by->id,
                'attempted_at' => now(),
                'outcome' => $outcome,
                'notes' => $data['notes'] ?? null,
            ]);

            $call->fill([
                'attempt_count' => $call->attempt_count + 1,
                'last_attempt_at' => now(),
                'outcome' => $outcome,
                'notes' => $data['notes'] ?? $call->notes,
                'action_required' => $data['action_required'] ?? $call->action_required,
            ]);

            $note = null;

            if ($outcome->isSuccessfulContact() || (bool) ($data['close_call'] ?? false)) {
                $call->fill([
                    'status' => CallStatus::Completed,
                    'completed_at' => now(),
                    'completed_by_id' => $by->id,
                    'next_follow_up_at' => filled($data['next_follow_up_at'] ?? null) ? Carbon::parse((string) $data['next_follow_up_at']) : null,
                ])->save();

                $note = $this->createNote($call, $by, $supportStatus);

                if ($call->next_follow_up_at !== null) {
                    $this->scheduleCall->handle($call, $call->next_follow_up_at, parent: $call);
                } else {
                    $this->scheduleCall->nextFromPlan($call);
                }
            } elseif (! $call->hasAttemptsRemaining()) {
                $call->fill(['status' => CallStatus::Missed])->save();

                $note = $this->createNote($call, $by, $supportStatus);

                TeamManagers::for($call->team_id)
                    ->each(fn (User $manager) => $manager->notify(new CallAttemptsExhaustedNotification($call)));

                $this->scheduleCall->nextFromPlan($call);
            } else {
                $call->due_at = filled($data['retry_at'] ?? null)
                    ? Carbon::parse((string) $data['retry_at'])
                    : self::defaultRetryAt($call);
                $call->save();

                if ($raiseConcern) {
                    $note = $this->createNote($call, $by, $supportStatus);
                }
            }

            if ($raiseConcern && $note !== null && $supportStatus !== null) {
                $call->forceFill(['safeguarding_raised' => true])->save();

                if ($call->serviceUser !== null) {
                    ServiceUserSupportStatus::flag($call->serviceUser, $note, $supportStatus);
                }
            }

            return $call->refresh();
        });
    }

    /**
     * Next working day at the same time the call was due.
     */
    public static function defaultRetryAt(Call $call): Carbon
    {
        return now()->addWeekday()->setTimeFrom($call->due_at);
    }

    private function createNote(Call $call, User $by, ?SupportStatus $supportStatus): ?Note
    {
        $person = People::query()->find($call->people_id);

        if ($person === null) {
            return null;
        }

        $body = collect([
            '<p><strong>Outcome:</strong> '.e($call->outcome?->getLabel()).' (attempt '.$call->attempt_count.')</p>',
            filled($call->notes) ? '<p>'.nl2br(e($call->notes)).'</p>' : null,
            filled($call->action_required) ? '<p><strong>Action required:</strong> '.nl2br(e($call->action_required)).'</p>' : null,
            $call->next_follow_up_at !== null ? '<p><strong>Next follow-up:</strong> '.$call->next_follow_up_at->format('d M Y, H:i').'</p>' : null,
        ])->filter()->implode('');

        /** @var Note $note */
        $note = $person->notes()->create([
            'title' => 'Liaison call – '.now()->format('d M Y').' ('.$call->status->getLabel().')',
            'body' => $body,
            'team_id' => $call->team_id,
            'creator_id' => $by->id,
            'creation_source' => CreationSource::WEB,
            'support_status' => $supportStatus,
        ], ['team_id' => $call->team_id]);

        $call->forceFill(['note_id' => $note->id])->save();

        return $note;
    }
}
