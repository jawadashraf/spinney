<?php

declare(strict_types=1);

use App\Actions\Calls\RecordCallOutcome;
use App\Enums\CallOutcome;
use App\Enums\CallStatus;
use App\Enums\SupportStatus;
use App\Events\ServiceUserNeedsAttention;
use App\Models\CallPlan;
use App\Models\People;
use App\Models\ServiceUserProfile;
use App\Models\Team;
use App\Models\User;
use App\Notifications\CallAttemptsExhaustedNotification;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Notification::fake();

    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();
    setPermissionsTeamId($this->team->id);

    $this->liaison = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->liaison->assignRole('liaison');
    actingAs($this->liaison);

    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($this->team);

    $this->plan = CallPlan::factory()->create([
        'team_id' => $this->team->id,
        'assigned_user_id' => $this->liaison->id,
        'starts_on' => today(),
        'preferred_time' => '10:00',
    ]);

    $this->call = $this->plan->calls()->sole();
});

it('completes an answered call, records a note and schedules the follow-up', function () {
    $followUpAt = now()->addWeek()->setTime(11, 0);

    $call = app(RecordCallOutcome::class)->handle($this->call, $this->liaison, [
        'outcome' => CallOutcome::Answered->value,
        'notes' => 'Doing well this week.',
        'action_required' => 'Post the leaflet.',
        'next_follow_up_at' => $followUpAt->toDateTimeString(),
    ]);

    expect($call->status)->toBe(CallStatus::Completed)
        ->and($call->completed_by_id)->toBe($this->liaison->id)
        ->and($call->action_required)->toBe('Post the leaflet.')
        ->and($call->attempts)->toHaveCount(1)
        ->and($call->note_id)->not->toBeNull()
        ->and(People::query()->find($call->people_id)->notes()->whereKey($call->note_id)->exists())->toBeTrue();

    $followUp = $call->followUpCall;

    expect($followUp)->not->toBeNull()
        ->and($followUp->status)->toBe(CallStatus::Scheduled)
        ->and($followUp->call_plan_id)->toBe($this->plan->id)
        ->and($followUp->assigned_user_id)->toBe($this->liaison->id)
        ->and($followUp->due_at->toDateTimeString())->toBe($followUpAt->toDateTimeString());
});

it('keeps an unanswered call open and moves it to the retry time', function () {
    $originalDueAt = $this->call->original_due_at->toDateTimeString();
    $retryAt = now()->addDay()->setTime(9, 0);

    $call = app(RecordCallOutcome::class)->handle($this->call, $this->liaison, [
        'outcome' => CallOutcome::NoAnswer->value,
        'notes' => 'Rang out.',
        'retry_at' => $retryAt->toDateTimeString(),
    ]);

    expect($call->status)->toBe(CallStatus::Scheduled)
        ->and($call->attempt_count)->toBe(1)
        ->and($call->outcome)->toBe(CallOutcome::NoAnswer)
        ->and($call->due_at->toDateTimeString())->toBe($retryAt->toDateTimeString())
        ->and($call->original_due_at->toDateTimeString())->toBe($originalDueAt)
        ->and($call->followUpCall)->toBeNull();
});

it('marks the call missed after the last attempt, alerts managers and schedules the next regular call', function () {
    $manager = User::factory()->create(['current_team_id' => $this->team->id]);
    $manager->assignRole('manager');

    $this->call->update(['attempt_count' => 2]);

    $call = app(RecordCallOutcome::class)->handle($this->call, $this->liaison, [
        'outcome' => CallOutcome::Voicemail->value,
        'notes' => 'Left a message.',
    ]);

    expect($call->status)->toBe(CallStatus::Missed)
        ->and($call->attempt_count)->toBe(3);

    Notification::assertSentTo($manager, CallAttemptsExhaustedNotification::class);

    $nextCall = $call->followUpCall;

    expect($nextCall)->not->toBeNull()
        ->and($nextCall->status)->toBe(CallStatus::Scheduled)
        ->and($nextCall->due_at->isFuture())->toBeTrue();
});

it('closes an unanswered call without retries when requested', function () {
    $call = app(RecordCallOutcome::class)->handle($this->call, $this->liaison, [
        'outcome' => CallOutcome::WrongNumber->value,
        'notes' => 'Number no longer in use.',
        'close_call' => true,
    ]);

    expect($call->status)->toBe(CallStatus::Completed)
        ->and($call->followUpCall)->toBeNull();
});

it('raises a support concern on the service user', function () {
    Event::fake([ServiceUserNeedsAttention::class]);

    ServiceUserProfile::factory()->create([
        'team_id' => $this->team->id,
        'person_id' => $this->call->people_id,
    ]);

    $call = app(RecordCallOutcome::class)->handle($this->call, $this->liaison, [
        'outcome' => CallOutcome::Answered->value,
        'notes' => 'Sounded very low.',
        'raise_concern' => true,
        'support_status' => SupportStatus::UrgentAttention->value,
    ]);

    expect($call->safeguarding_raised)->toBeTrue()
        ->and(ServiceUserProfile::query()->where('person_id', $call->people_id)->value('support_status'))->toBe(SupportStatus::UrgentAttention);

    Event::assertDispatched(ServiceUserNeedsAttention::class);
});
