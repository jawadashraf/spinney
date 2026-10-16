<?php

declare(strict_types=1);

namespace App\Actions\Calls;

use App\Enums\CallOutcome;
use App\Enums\PbxCallResult;
use App\Filament\Resources\Calls\CallResource;
use App\Models\Call;
use App\Models\PbxCallLog;
use App\Models\ServiceUser;
use App\Models\Team;
use App\Models\User;
use App\Support\PhoneNumber;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Records a finished call reported by the phone system's call journal and applies it to the
 * service user's open liaison call: an answered call adds an attempt that waits for the
 * liaison's notes, and an unanswered outbound call is recorded as a failed attempt.
 */
final readonly class LogPhoneSystemCall
{
    public function __construct(private RecordCallOutcome $recordCallOutcome) {}

    /**
     * @param  array{linked_id: string, direction: string, disposition: string, status?: string|null, talk_duration_sec?: int|string|null, started_at: int|string, ended_at?: int|string|null, crm_user_id?: string|null, contact_id?: string|null, contact_number?: string|null, recording_url?: string|null}  $data
     */
    public function handle(Team $team, array $data): PbxCallLog
    {
        setPermissionsTeamId($team->id);

        $crmUserId = (string) ($data['crm_user_id'] ?? '');

        $existing = $this->findExisting($team, $data['linked_id'], $crmUserId);

        if ($existing !== null) {
            return $existing;
        }

        $liaison = $this->findLiaison($crmUserId);
        $contactNumber = PhoneNumber::normalize($data['contact_number'] ?? null);
        $serviceUser = $this->findServiceUser($team, $data['contact_id'] ?? null, $contactNumber);

        $log = new PbxCallLog([
            'team_id' => $team->id,
            'linked_id' => $data['linked_id'],
            'crm_user_id' => $crmUserId,
            'user_id' => $liaison?->id,
            'people_id' => $serviceUser?->id,
            'direction' => $data['direction'],
            'disposition' => $data['disposition'],
            'status' => $data['status'] ?? null,
            'talk_duration_sec' => (int) ($data['talk_duration_sec'] ?? 0),
            'started_at' => Carbon::createFromTimestamp((int) $data['started_at']),
            'ended_at' => filled($data['ended_at'] ?? null) ? Carbon::createFromTimestamp((int) $data['ended_at']) : null,
            'contact_number' => $contactNumber,
            'recording_url' => filled($data['recording_url'] ?? null) ? $data['recording_url'] : null,
            'result' => PbxCallResult::LoggedOnly,
        ]);

        $call = $liaison !== null && $serviceUser !== null ? $this->findOpenCall($team, $serviceUser, $liaison) : null;

        try {
            return DB::transaction(function () use ($log, $call, $liaison): PbxCallLog {
                if ($call !== null && $liaison !== null) {
                    $log->call_id = $call->id;
                    $this->actingAs($liaison, fn () => $this->applyToCall($log, $call, $liaison));
                }

                $log->save();

                return $log;
            });
        } catch (UniqueConstraintViolationException) {
            // The PBX retried the same call while the first request was still being handled.
            return $this->findExisting($team, $data['linked_id'], $crmUserId) ?? throw new RuntimeException('Duplicate phone system call could not be found.');
        }
    }

    private function applyToCall(PbxCallLog $log, Call $call, User $liaison): void
    {
        if ($call->isAwaitingNotes()) {
            return;
        }

        if ($log->isAnswered()) {
            $attempt = $call->attempts()->create([
                'team_id' => $call->team_id,
                'user_id' => $liaison->id,
                'attempted_at' => $log->started_at,
                'outcome' => CallOutcome::Answered,
            ]);

            $call->fill([
                'attempt_count' => $call->attempt_count + 1,
                'last_attempt_at' => $log->started_at,
                'outcome' => CallOutcome::Answered,
            ])->save();

            $log->call_attempt_id = $attempt->id;
            $log->result = PbxCallResult::AwaitingNotes;

            return;
        }

        if (! $log->isOutbound() || $log->durationInSeconds() < (int) config('services.yeastar.misdial_seconds')) {
            return;
        }

        $this->recordCallOutcome->handle($call, $liaison, [
            'outcome' => $log->disposition === PbxCallLog::DISPOSITION_VOICEMAIL ? CallOutcome::Voicemail : CallOutcome::NoAnswer,
            'notes' => 'Logged by the phone system: '.mb_strtolower($log->disposition).'.',
            'attempted_at' => $log->started_at,
        ]);

        $log->call_attempt_id = $call->attempts()->reorder()->latest('id')->value('id');
        $log->result = PbxCallResult::AttemptRecorded;
    }

    private function findExisting(Team $team, string $linkedId, string $crmUserId): ?PbxCallLog
    {
        return PbxCallLog::query()
            ->where('team_id', $team->id)
            ->where('linked_id', $linkedId)
            ->where('crm_user_id', $crmUserId)
            ->first();
    }

    private function findLiaison(string $crmUserId): ?User
    {
        if (! ctype_digit($crmUserId)) {
            return null;
        }

        return CallResource::scopeAssignableUsers(User::query())->whereKey((int) $crmUserId)->first();
    }

    private function findServiceUser(Team $team, ?string $contactId, ?string $contactNumber): ?ServiceUser
    {
        $serviceUsers = ServiceUser::query()->where('team_id', $team->id);

        if ($contactId !== null && ctype_digit($contactId)) {
            $serviceUser = (clone $serviceUsers)->whereKey((int) $contactId)->first();

            if ($serviceUser !== null) {
                return $serviceUser;
            }
        }

        if ($contactNumber === null) {
            return null;
        }

        return $serviceUsers->where('phone_normalized', $contactNumber)->oldest('id')->first();
    }

    /**
     * The service user's earliest open call due by the end of the UK day, preferring the liaison's own.
     */
    private function findOpenCall(Team $team, ServiceUser $serviceUser, User $liaison): ?Call
    {
        return Call::query()
            ->dueByEndOfUkDay()
            ->visibleTo($liaison)
            ->where('calls.team_id', $team->id)
            ->where('calls.people_id', $serviceUser->id)
            ->orderByRaw('case when calls.assigned_user_id = ? then 0 else 1 end', [$liaison->id])
            ->orderBy('calls.due_at')
            ->first();
    }

    /**
     * Run as the liaison so follow-up calls, notes and activity entries are attributed to them,
     * exactly as when they record an outcome in the app.
     */
    private function actingAs(User $liaison, Closure $callback): void
    {
        $guard = auth()->guard('web');
        $previous = $guard->hasUser() ? $guard->user() : null;

        $guard->setUser($liaison);

        try {
            $callback();
        } finally {
            $previous !== null ? $guard->setUser($previous) : $guard->forgetUser();
        }
    }
}
