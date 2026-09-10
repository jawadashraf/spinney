<?php

declare(strict_types=1);

use App\Enums\CallFrequency;
use App\Enums\CallStatus;
use App\Filament\Resources\CallPlans\Pages\CreateCallPlan;
use App\Models\Call;
use App\Models\CallPlan;
use App\Models\People;
use App\Models\Team;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    Notification::fake();

    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();
    setPermissionsTeamId($this->team->id);

    $this->admin = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->admin->assignRole('admin');

    actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($this->team);

    $this->serviceUser = People::factory()->create([
        'team_id' => $this->team->id,
        'type' => 'service_user',
        'is_service_user' => true,
    ]);
});

it('schedules the first call when an active plan is created', function () {
    $liaison = User::factory()->create(['current_team_id' => $this->team->id]);

    $plan = CallPlan::factory()->create([
        'team_id' => $this->team->id,
        'people_id' => $this->serviceUser->id,
        'assigned_user_id' => $liaison->id,
        'starts_on' => today()->addDay(),
        'preferred_time' => '14:30',
    ]);

    $call = $plan->calls()->sole();

    expect($call->status)->toBe(CallStatus::Scheduled)
        ->and($call->assigned_user_id)->toBe($liaison->id)
        ->and($call->team_id)->toBe($this->team->id)
        ->and($call->due_at->toDateTimeString())->toBe(today()->addDay()->setTime(14, 30)->toDateTimeString());
});

it('does not schedule calls for an inactive plan', function () {
    $plan = CallPlan::factory()->inactive()->create([
        'team_id' => $this->team->id,
        'people_id' => $this->serviceUser->id,
    ]);

    expect($plan->calls()->count())->toBe(0);
});

it('calculates the next due date from the frequency', function (CallFrequency $frequency, ?int $intervalDays, string $expected) {
    $next = $frequency->nextDueFrom(CarbonImmutable::parse('2026-01-31 10:00:00'), $intervalDays);

    expect($next->toDateTimeString())->toBe($expected);
})->with([
    'weekly' => [CallFrequency::Weekly, null, '2026-02-07 10:00:00'],
    'fortnightly' => [CallFrequency::Fortnightly, null, '2026-02-14 10:00:00'],
    'monthly without overflow' => [CallFrequency::Monthly, null, '2026-02-28 10:00:00'],
    'quarterly' => [CallFrequency::Quarterly, null, '2026-04-30 10:00:00'],
    'custom interval' => [CallFrequency::Custom, 10, '2026-02-10 10:00:00'],
]);

it('reassigns only open calls when the plan liaison changes', function () {
    $liaison = User::factory()->create(['current_team_id' => $this->team->id]);

    $plan = CallPlan::factory()->create([
        'team_id' => $this->team->id,
        'people_id' => $this->serviceUser->id,
    ]);

    $completedCall = Call::factory()->completed()->create([
        'team_id' => $this->team->id,
        'people_id' => $this->serviceUser->id,
        'call_plan_id' => $plan->id,
    ]);

    $plan->update(['assigned_user_id' => $liaison->id]);

    expect($plan->calls()->open()->sole()->assigned_user_id)->toBe($liaison->id)
        ->and($completedCall->fresh()->assigned_user_id)->toBeNull();
});

it('cancels open calls when the plan is deactivated', function () {
    $plan = CallPlan::factory()->create([
        'team_id' => $this->team->id,
        'people_id' => $this->serviceUser->id,
    ]);

    $plan->update(['is_active' => false]);

    expect($plan->calls()->open()->count())->toBe(0)
        ->and($plan->calls()->where('status', CallStatus::Cancelled)->count())->toBe(1);
});

it('creates a call plan from the form', function () {
    livewire(CreateCallPlan::class)
        ->fillForm([
            'people_id' => $this->serviceUser->id,
            'frequency' => CallFrequency::Fortnightly->value,
            'starts_on' => today()->toDateString(),
            'max_attempts' => 3,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $plan = CallPlan::query()->where('people_id', $this->serviceUser->id)->sole();

    expect($plan->team_id)->toBe($this->team->id)
        ->and($plan->frequency)->toBe(CallFrequency::Fortnightly)
        ->and($plan->calls()->count())->toBe(1);
});

it('validates the call plan form', function (array $data, array $errors) {
    livewire(CreateCallPlan::class)
        ->fillForm([
            'people_id' => $this->serviceUser->id,
            'frequency' => CallFrequency::Weekly->value,
            'starts_on' => today()->toDateString(),
            'max_attempts' => 3,
            ...$data,
        ])
        ->call('create')
        ->assertHasFormErrors($errors);
})->with([
    'service user is required' => [['people_id' => null], ['people_id' => 'required']],
    'interval is required for a custom frequency' => [['frequency' => CallFrequency::Custom->value, 'interval_days' => null], ['interval_days' => 'required']],
    'max attempts is at least 1' => [['max_attempts' => 0], ['max_attempts' => 'min']],
    'max attempts is at most 10' => [['max_attempts' => 11], ['max_attempts' => 'max']],
    'end date is not before start date' => [['ends_on' => today()->subDay()->toDateString()], ['ends_on' => 'after_or_equal']],
]);

it('prevents a second active plan for the same service user', function () {
    CallPlan::factory()->create([
        'team_id' => $this->team->id,
        'people_id' => $this->serviceUser->id,
    ]);

    livewire(CreateCallPlan::class)
        ->fillForm([
            'people_id' => $this->serviceUser->id,
            'frequency' => CallFrequency::Weekly->value,
            'starts_on' => today()->toDateString(),
            'max_attempts' => 3,
        ])
        ->call('create')
        ->assertHasFormErrors(['people_id']);
});
