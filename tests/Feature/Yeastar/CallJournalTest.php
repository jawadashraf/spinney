<?php

declare(strict_types=1);

use App\Enums\CallOutcome;
use App\Enums\CallStatus;
use App\Enums\PbxCallResult;
use App\Models\Call;
use App\Models\CallPlan;
use App\Models\Company;
use App\Models\PbxCallLog;
use App\Models\People;
use App\Models\Team;
use App\Models\User;
use App\Notifications\CallAttemptsExhaustedNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;

/**
 * A call journal body as the Spinney.xml template renders it: every value is a string.
 *
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function callJournalPayload(User $liaison, People $serviceUser, array $overrides = []): array
{
    $startedAt = now()->subMinutes(5)->timestamp;

    return [
        'linked_id' => '1791457200.101',
        'direction' => 'outbound',
        'disposition' => 'ANSWERED',
        'status' => 'Completed',
        'talk_duration_sec' => '252',
        'started_at' => (string) $startedAt,
        'ended_at' => (string) ($startedAt + 270),
        'crm_user_id' => (string) $liaison->id,
        'contact_id' => (string) $serviceUser->id,
        'contact_number' => '+447438356414',
        'recording_url' => 'https://pbx.example.test/recordings/1791457200.101.wav',
        ...$overrides,
    ];
}

/**
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function unansweredCallJournalPayload(User $liaison, People $serviceUser, array $overrides = []): array
{
    $startedAt = now()->subMinutes(5)->timestamp;

    return callJournalPayload($liaison, $serviceUser, [
        'disposition' => 'NO ANSWER',
        'status' => 'Missed',
        'talk_duration_sec' => '0',
        'started_at' => (string) $startedAt,
        'ended_at' => (string) ($startedAt + 30),
        'recording_url' => '',
        ...$overrides,
    ]);
}

beforeEach(function () {
    Notification::fake();
    $this->travelTo('2026-10-07 10:00:00');

    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();
    setPermissionsTeamId($this->team->id);

    $this->liaison = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->liaison->assignRole('liaison');

    $this->serviceUser = People::factory()->serviceUser()->create(['team_id' => $this->team->id, 'phone' => '07438 356414']);
});

it('returns 401 when the phone system sends no credentials', function () {
    yeastarHeaders($this->team->id);

    $this->postJson(route('yeastar.call-journal.store'), callJournalPayload($this->liaison, $this->serviceUser))
        ->assertUnauthorized();

    expect(PbxCallLog::query()->exists())->toBeFalse();
});

it('returns 422 when the required call details are missing', function () {
    $this->withHeaders(yeastarHeaders($this->team->id))
        ->postJson(route('yeastar.call-journal.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'linked_id' => 'The linked id field is required.',
            'direction' => 'The direction field is required.',
            'disposition' => 'The disposition field is required.',
            'started_at' => 'The started at field is required.',
        ]);
});

it('returns 422 for an unknown call direction', function () {
    $this->withHeaders(yeastarHeaders($this->team->id))
        ->postJson(route('yeastar.call-journal.store'), callJournalPayload($this->liaison, $this->serviceUser, ['direction' => 'sideways']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['direction' => 'The selected direction is invalid.']);
});

it('adds an answered attempt to the due call and leaves it open for the liaison notes', function () {
    $call = Call::factory()->assignedTo($this->liaison)->create(['team_id' => $this->team->id, 'people_id' => $this->serviceUser->id, 'due_at' => now()->setTime(11, 0)]);

    $response = $this->withHeaders(yeastarHeaders($this->team->id))
        ->postJson(route('yeastar.call-journal.store'), callJournalPayload($this->liaison, $this->serviceUser))
        ->assertCreated();

    $log = PbxCallLog::query()->sole();
    $response->assertExactJson(['id' => (string) $log->id]);

    $call->refresh();
    $attempt = $call->attempts()->sole();

    expect($call->status)->toBe(CallStatus::Scheduled)
        ->and($call->isAwaitingNotes())->toBeTrue()
        ->and($call->attempt_count)->toBe(1)
        ->and($attempt->outcome)->toBe(CallOutcome::Answered)
        ->and($attempt->user_id)->toBe($this->liaison->id)
        ->and($attempt->notes)->toBeNull()
        ->and($attempt->attempted_at->toDateTimeString())->toBe(now()->subMinutes(5)->toDateTimeString())
        ->and($log->result)->toBe(PbxCallResult::AwaitingNotes)
        ->and($log->call_id)->toBe($call->id)
        ->and($log->call_attempt_id)->toBe($attempt->id)
        ->and($log->talk_duration_sec)->toBe(252)
        ->and($log->recording_url)->toBe('https://pbx.example.test/recordings/1791457200.101.wav');
});

it('matches an answered inbound call to the service user by number when the phone system sends no contact id', function () {
    $call = Call::factory()->assignedTo($this->liaison)->overdue()->create(['team_id' => $this->team->id, 'people_id' => $this->serviceUser->id]);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->postJson(route('yeastar.call-journal.store'), callJournalPayload($this->liaison, $this->serviceUser, [
            'direction' => 'inbound',
            'contact_id' => '',
            'contact_number' => '07438356414',
        ]))
        ->assertCreated();

    expect($call->refresh()->isAwaitingNotes())->toBeTrue()
        ->and(PbxCallLog::query()->sole()->people_id)->toBe($this->serviceUser->id);
});

it('records an unanswered outbound call as a failed attempt and moves the call to the next working day', function () {
    $call = Call::factory()->assignedTo($this->liaison)->create(['team_id' => $this->team->id, 'people_id' => $this->serviceUser->id, 'due_at' => now()->setTime(9, 30)]);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->postJson(route('yeastar.call-journal.store'), unansweredCallJournalPayload($this->liaison, $this->serviceUser))
        ->assertCreated();

    $call->refresh();
    $attempt = $call->attempts()->sole();

    expect($call->status)->toBe(CallStatus::Scheduled)
        ->and($call->outcome)->toBe(CallOutcome::NoAnswer)
        ->and($call->attempt_count)->toBe(1)
        ->and($call->due_at->toDateTimeString())->toBe('2026-10-08 09:30:00')
        ->and($attempt->notes)->toBe('Logged by the phone system: no answer.')
        ->and($attempt->attempted_at->toDateTimeString())->toBe(now()->subMinutes(5)->toDateTimeString())
        ->and(PbxCallLog::query()->sole()->result)->toBe(PbxCallResult::AttemptRecorded)
        ->and(PbxCallLog::query()->sole()->call_attempt_id)->toBe($attempt->id);
});

it('records an outbound call that reached voicemail as a voicemail attempt', function () {
    $call = Call::factory()->assignedTo($this->liaison)->overdue()->create(['team_id' => $this->team->id, 'people_id' => $this->serviceUser->id]);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->postJson(route('yeastar.call-journal.store'), unansweredCallJournalPayload($this->liaison, $this->serviceUser, ['disposition' => 'VOICEMAIL']))
        ->assertCreated();

    expect($call->refresh()->outcome)->toBe(CallOutcome::Voicemail);
});

it('marks the call missed after the last unanswered attempt, alerts managers and books the next plan call', function () {
    $manager = User::factory()->create(['current_team_id' => $this->team->id]);
    $manager->assignRole('manager');

    $plan = CallPlan::factory()->create([
        'team_id' => $this->team->id,
        'people_id' => $this->serviceUser->id,
        'assigned_user_id' => $this->liaison->id,
        'starts_on' => today(),
        'preferred_time' => '09:00',
    ]);
    $call = $plan->calls()->sole();
    $call->update(['attempt_count' => 2]);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->postJson(route('yeastar.call-journal.store'), unansweredCallJournalPayload($this->liaison, $this->serviceUser))
        ->assertCreated();

    $call->refresh();

    expect($call->status)->toBe(CallStatus::Missed)
        ->and($call->followUpCall?->status)->toBe(CallStatus::Scheduled)
        ->and($plan->calls()->open()->count())->toBe(1);

    Notification::assertSentTo($manager, CallAttemptsExhaustedNotification::class);
});

it('does not count an outbound call that was hung up before the misdial threshold', function () {
    $call = Call::factory()->assignedTo($this->liaison)->overdue()->create(['team_id' => $this->team->id, 'people_id' => $this->serviceUser->id]);
    $startedAt = now()->subMinute()->timestamp;

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->postJson(route('yeastar.call-journal.store'), unansweredCallJournalPayload($this->liaison, $this->serviceUser, [
            'started_at' => (string) $startedAt,
            'ended_at' => (string) ($startedAt + 5),
        ]))
        ->assertCreated();

    expect($call->refresh()->attempt_count)->toBe(0)
        ->and(PbxCallLog::query()->sole()->result)->toBe(PbxCallResult::LoggedOnly);
});

it('only logs a missed inbound call', function () {
    $call = Call::factory()->assignedTo($this->liaison)->overdue()->create(['team_id' => $this->team->id, 'people_id' => $this->serviceUser->id]);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->postJson(route('yeastar.call-journal.store'), unansweredCallJournalPayload($this->liaison, $this->serviceUser, ['direction' => 'inbound']))
        ->assertCreated();

    expect($call->refresh()->attempt_count)->toBe(0)
        ->and(PbxCallLog::query()->sole())
        ->result->toBe(PbxCallResult::LoggedOnly)
        ->call_id->toBe($call->id);
});

it('only logs a call to a service user whose next call is due on a later day', function () {
    $call = Call::factory()->assignedTo($this->liaison)->create(['team_id' => $this->team->id, 'people_id' => $this->serviceUser->id, 'due_at' => now()->addDay()]);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->postJson(route('yeastar.call-journal.store'), callJournalPayload($this->liaison, $this->serviceUser))
        ->assertCreated();

    expect($call->refresh()->attempt_count)->toBe(0)
        ->and(PbxCallLog::query()->sole())
        ->result->toBe(PbxCallResult::LoggedOnly)
        ->people_id->toBe($this->serviceUser->id)
        ->call_id->toBeNull();
});

it('does not add a second attempt to a call that is already awaiting notes', function () {
    $call = Call::factory()->assignedTo($this->liaison)->awaitingNotes()->create(['team_id' => $this->team->id, 'people_id' => $this->serviceUser->id, 'due_at' => now()->setTime(9, 0)]);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->postJson(route('yeastar.call-journal.store'), callJournalPayload($this->liaison, $this->serviceUser, ['linked_id' => '1791457999.555']))
        ->assertCreated();

    expect($call->refresh()->attempt_count)->toBe(1)
        ->and($call->attempts()->count())->toBe(1);
});

it('does not touch a call assigned to another liaison', function () {
    $otherLiaison = User::factory()->create(['current_team_id' => $this->team->id]);
    $otherLiaison->assignRole('liaison');
    $call = Call::factory()->assignedTo($otherLiaison)->overdue()->create(['team_id' => $this->team->id, 'people_id' => $this->serviceUser->id]);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->postJson(route('yeastar.call-journal.store'), callJournalPayload($this->liaison, $this->serviceUser))
        ->assertCreated();

    expect($call->refresh()->attempt_count)->toBe(0)
        ->and(PbxCallLog::query()->sole()->call_id)->toBeNull();
});

it('prefers the liaison own call over an earlier unassigned call', function () {
    Call::factory()->create(['team_id' => $this->team->id, 'people_id' => $this->serviceUser->id, 'due_at' => now()->subDays(2)]);
    $ownCall = Call::factory()->assignedTo($this->liaison)->create(['team_id' => $this->team->id, 'people_id' => $this->serviceUser->id, 'due_at' => now()->subDay()]);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->postJson(route('yeastar.call-journal.store'), callJournalPayload($this->liaison, $this->serviceUser))
        ->assertCreated();

    expect(PbxCallLog::query()->sole()->call_id)->toBe($ownCall->id);
});

it('only logs a call from an extension that is not mapped to a liaison', function () {
    $call = Call::factory()->assignedTo($this->liaison)->overdue()->create(['team_id' => $this->team->id, 'people_id' => $this->serviceUser->id]);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->postJson(route('yeastar.call-journal.store'), callJournalPayload($this->liaison, $this->serviceUser, ['crm_user_id' => '']))
        ->assertCreated();

    expect($call->refresh()->attempt_count)->toBe(0)
        ->and(PbxCallLog::query()->sole())
        ->user_id->toBeNull()
        ->result->toBe(PbxCallResult::LoggedOnly);
});

it('does not match a service user from another team', function () {
    $otherServiceUser = People::factory()->serviceUser()->create(['team_id' => Team::factory()->create()->id, 'phone' => '+447438356414']);
    $this->serviceUser->update(['phone' => null]);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->postJson(route('yeastar.call-journal.store'), callJournalPayload($this->liaison, $otherServiceUser))
        ->assertCreated();

    expect(PbxCallLog::query()->sole()->people_id)->toBeNull();
});

it('returns the existing log without adding another attempt when the phone system resends a call', function () {
    $call = Call::factory()->assignedTo($this->liaison)->overdue()->create(['team_id' => $this->team->id, 'people_id' => $this->serviceUser->id]);
    $headers = yeastarHeaders($this->team->id);
    $payload = unansweredCallJournalPayload($this->liaison, $this->serviceUser);

    $first = $this->withHeaders($headers)->postJson(route('yeastar.call-journal.store'), $payload)->assertCreated();

    $this->withHeaders($headers)
        ->postJson(route('yeastar.call-journal.store'), $payload)
        ->assertOk()
        ->assertExactJson(['id' => $first->json('id')]);

    expect(PbxCallLog::query()->count())->toBe(1)
        ->and($call->refresh()->attempt_count)->toBe(1);
});

it('attributes the call changes to the liaison without leaving them signed in', function () {
    $call = Call::factory()->assignedTo($this->liaison)->overdue()->create(['team_id' => $this->team->id, 'people_id' => $this->serviceUser->id]);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->postJson(route('yeastar.call-journal.store'), callJournalPayload($this->liaison, $this->serviceUser))
        ->assertCreated();

    $activity = Activity::query()->whereMorphedTo('subject', $call)->latest('id')->firstOrFail();

    expect($activity->causer_id)->toBe($this->liaison->id)
        ->and(auth()->guard('web')->hasUser())->toBeFalse();
});

it('applies the call when the phone system sends the prefixed contact id', function () {
    $call = Call::factory()->assignedTo($this->liaison)->overdue()->create(['team_id' => $this->team->id, 'people_id' => $this->serviceUser->id]);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->postJson(route('yeastar.call-journal.store'), callJournalPayload($this->liaison, $this->serviceUser, ['contact_id' => 'person-'.$this->serviceUser->id]))
        ->assertCreated();

    expect($call->refresh()->isAwaitingNotes())->toBeTrue();
});

it('logs a call with a relative against the relative without touching any liaison call', function () {
    $relative = People::factory()->create(['team_id' => $this->team->id, 'type' => 'relative', 'phone' => '07700 900123']);
    $call = Call::factory()->assignedTo($this->liaison)->overdue()->create(['team_id' => $this->team->id, 'people_id' => $this->serviceUser->id]);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->postJson(route('yeastar.call-journal.store'), callJournalPayload($this->liaison, $relative, ['contact_number' => '+447700900123']))
        ->assertCreated();

    expect($call->refresh()->attempt_count)->toBe(0)
        ->and(PbxCallLog::query()->sole())
        ->people_id->toBe($relative->id)
        ->company_id->toBeNull()
        ->call_id->toBeNull()
        ->result->toBe(PbxCallResult::LoggedOnly);
});

it('logs a call with a company against the company', function () {
    $company = Company::factory()->create(['team_id' => $this->team->id, 'phone' => '0116 496 0000']);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->postJson(route('yeastar.call-journal.store'), callJournalPayload($this->liaison, $this->serviceUser, [
            'contact_id' => 'company-'.$company->id,
            'contact_number' => '01164960000',
        ]))
        ->assertCreated();

    expect(PbxCallLog::query()->sole())
        ->company_id->toBe($company->id)
        ->people_id->toBeNull()
        ->result->toBe(PbxCallResult::LoggedOnly);
});

it('matches a number shared with a relative to the service user', function () {
    People::factory()->create(['team_id' => $this->team->id, 'type' => 'relative', 'phone' => '+447438356414']);
    $call = Call::factory()->assignedTo($this->liaison)->overdue()->create(['team_id' => $this->team->id, 'people_id' => $this->serviceUser->id]);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->postJson(route('yeastar.call-journal.store'), callJournalPayload($this->liaison, $this->serviceUser, ['contact_id' => '']))
        ->assertCreated();

    expect($call->refresh()->isAwaitingNotes())->toBeTrue()
        ->and(PbxCallLog::query()->sole()->people_id)->toBe($this->serviceUser->id);
});

it('logs a call from an unknown number as unmatched', function () {
    $this->serviceUser->update(['phone' => null]);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->postJson(route('yeastar.call-journal.store'), callJournalPayload($this->liaison, $this->serviceUser, [
            'contact_id' => 'unknown-447438356414',
            'contact_number' => '07438356414',
        ]))
        ->assertCreated();

    expect(PbxCallLog::query()->unmatched()->sole())
        ->contact_number->toBe('+447438356414')
        ->user_id->toBe($this->liaison->id);
});
