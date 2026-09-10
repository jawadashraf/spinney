<?php

declare(strict_types=1);

use App\Enums\CallStatus;
use App\Filament\Resources\Calls\Pages\CreateCall;
use App\Filament\Resources\Calls\Pages\ListCalls;
use App\Filament\Resources\ServiceUsers\Pages\EditServiceUser;
use App\Filament\Resources\ServiceUsers\RelationManagers\CallsRelationManager;
use App\Models\Call;
use App\Models\Department;
use App\Models\People;
use App\Models\Role;
use App\Models\ServiceUser;
use App\Models\Team;
use App\Models\User;
use App\Notifications\CallAssignedNotification;
use App\Support\CallPermissions;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    Notification::fake();

    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();
    setPermissionsTeamId($this->team->id);

    Role::firstOrCreate(['name' => 'volunteer_liaison', 'team_id' => $this->team->id], ['guard_name' => 'web']);
    CallPermissions::ensure($this->team->id);

    $this->liaisonDepartment = Department::query()->where('team_id', $this->team->id)->where('name', 'Liaison')->firstOrFail();
    $this->counselorDepartment = Department::query()->where('team_id', $this->team->id)->where('name', 'Counselor')->firstOrFail();

    $this->admin = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->admin->assignRole('admin');

    actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($this->team);

    $this->liaison = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->liaison->assignRole('liaison');
    $this->liaison->departments()->attach($this->liaisonDepartment, ['team_id' => $this->team->id]);

    $this->volunteer = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->volunteer->assignRole('volunteer_liaison');
});

describe('visibility', function () {
    it('shows a liaison their own and their department calls only', function () {
        $ownCall = Call::factory()->assignedTo($this->liaison)->create(['team_id' => $this->team->id]);
        $departmentCall = Call::factory()->create(['team_id' => $this->team->id, 'department_id' => $this->liaisonDepartment->id]);
        $otherCall = Call::factory()->assignedTo($this->admin)->create(['team_id' => $this->team->id, 'department_id' => $this->counselorDepartment->id]);

        actingAs($this->liaison);

        livewire(ListCalls::class)
            ->set('activeTab', 'all')
            ->assertCanSeeTableRecords([$ownCall, $departmentCall])
            ->assertCanNotSeeTableRecords([$otherCall]);
    });

    it('shows a volunteer liaison only the calls assigned to them', function () {
        $assignedCall = Call::factory()->assignedTo($this->volunteer)->create(['team_id' => $this->team->id]);
        $departmentCall = Call::factory()->create(['team_id' => $this->team->id, 'department_id' => $this->liaisonDepartment->id]);

        actingAs($this->volunteer);

        livewire(ListCalls::class)
            ->set('activeTab', 'all')
            ->assertCanSeeTableRecords([$assignedCall])
            ->assertCanNotSeeTableRecords([$departmentCall]);
    });

    it('lets a volunteer liaison open service users they have calls with', function () {
        $call = Call::factory()->assignedTo($this->volunteer)->create(['team_id' => $this->team->id]);
        $otherServiceUser = People::factory()->create(['team_id' => $this->team->id, 'type' => 'service_user', 'is_service_user' => true]);

        $visibleIds = ServiceUser::query()->visibleToVolunteerLiaison($this->volunteer)->pluck('people.id');

        expect($visibleIds)->toContain($call->people_id)
            ->not->toContain($otherServiceUser->id);
    });

    it('splits calls into overdue, upcoming and history tabs', function () {
        $overdueCall = Call::factory()->overdue()->create(['team_id' => $this->team->id]);
        $upcomingCall = Call::factory()->create(['team_id' => $this->team->id]);
        $completedCall = Call::factory()->completed()->create(['team_id' => $this->team->id]);

        actingAs($this->admin);

        livewire(ListCalls::class)
            ->set('activeTab', 'overdue')
            ->assertCanSeeTableRecords([$overdueCall])
            ->assertCanNotSeeTableRecords([$upcomingCall, $completedCall])
            ->set('activeTab', 'upcoming')
            ->assertCanSeeTableRecords([$upcomingCall])
            ->assertCanNotSeeTableRecords([$overdueCall, $completedCall])
            ->set('activeTab', 'history')
            ->assertCanSeeTableRecords([$completedCall])
            ->assertCanNotSeeTableRecords([$overdueCall, $upcomingCall]);
    });
});

describe('actions', function () {
    it('lets the assigned liaison record an outcome', function () {
        $call = Call::factory()->assignedTo($this->liaison)->create(['team_id' => $this->team->id]);

        actingAs($this->liaison);

        livewire(ListCalls::class)
            ->callAction(TestAction::make('recordOutcome')->table($call), data: [
                'outcome' => 'answered',
                'notes' => 'All good, no concerns.',
            ])
            ->assertHasNoFormErrors()
            ->assertNotified();

        expect($call->fresh()->status)->toBe(CallStatus::Completed);
    });

    it('hides reschedule and reassign actions from liaisons', function () {
        $call = Call::factory()->assignedTo($this->liaison)->create(['team_id' => $this->team->id]);

        actingAs($this->liaison);

        livewire(ListCalls::class)
            ->assertActionHidden(TestAction::make('reschedule')->table($call))
            ->assertActionHidden(TestAction::make('reassign')->table($call));
    });

    it('lets an admin change the due date and logs it', function () {
        $call = Call::factory()->assignedTo($this->liaison)->create(['team_id' => $this->team->id]);
        $newDueAt = now()->addDays(5)->setTime(15, 0);

        actingAs($this->admin);

        livewire(ListCalls::class)
            ->set('activeTab', 'all')
            ->callAction(TestAction::make('reschedule')->table($call), data: [
                'due_at' => $newDueAt->toDateTimeString(),
                'reason' => 'Service user on holiday',
            ])
            ->assertHasNoFormErrors()
            ->assertNotified();

        expect($call->fresh()->due_at->toDateTimeString())->toBe($newDueAt->toDateTimeString())
            ->and(Activity::query()->where('subject_id', $call->id)->where('description', 'Call rescheduled')->exists())->toBeTrue();
    });

    it('lets an admin assign a liaison to a call', function () {
        $call = Call::factory()->create(['team_id' => $this->team->id]);

        actingAs($this->admin);

        livewire(ListCalls::class)
            ->set('activeTab', 'all')
            ->callAction(TestAction::make('reassign')->table($call), data: [
                'assigned_user_id' => $this->liaison->id,
            ])
            ->assertHasNoFormErrors();

        expect($call->fresh()->assigned_user_id)->toBe($this->liaison->id);

        Notification::assertSentTo($this->liaison, CallAssignedNotification::class);
    });

    it('bulk reassigns selected calls', function () {
        $calls = Call::factory()->count(2)->create(['team_id' => $this->team->id]);

        actingAs($this->admin);

        livewire(ListCalls::class)
            ->set('activeTab', 'all')
            ->selectTableRecords($calls)
            ->callAction(TestAction::make('reassignSelected')->table()->bulk(), data: [
                'assigned_user_id' => $this->liaison->id,
            ])
            ->assertNotified();

        expect(Call::query()->whereKey($calls->modelKeys())->where('assigned_user_id', $this->liaison->id)->count())->toBe(2);
    });

    it('moves open calls in a date range to a covering liaison', function () {
        $cover = User::factory()->create(['current_team_id' => $this->team->id]);
        $cover->assignRole('liaison');

        $inRange = Call::factory()->assignedTo($this->liaison)->create(['team_id' => $this->team->id, 'due_at' => now()->addDays(2)]);
        $outOfRange = Call::factory()->assignedTo($this->liaison)->create(['team_id' => $this->team->id, 'due_at' => now()->addDays(20)]);
        $completed = Call::factory()->completed()->assignedTo($this->liaison)->create(['team_id' => $this->team->id, 'due_at' => now()->addDays(2)]);

        actingAs($this->admin);

        livewire(ListCalls::class)
            ->callAction('coverAbsence', data: [
                'from_user_id' => $this->liaison->id,
                'to_user_id' => $cover->id,
                'from' => today()->toDateString(),
                'until' => today()->addWeek()->toDateString(),
            ])
            ->assertHasNoFormErrors()
            ->assertNotified();

        expect($inRange->fresh()->assigned_user_id)->toBe($cover->id)
            ->and($outOfRange->fresh()->assigned_user_id)->toBe($this->liaison->id)
            ->and($completed->fresh()->assigned_user_id)->toBe($this->liaison->id);
    });
});

describe('create', function () {
    it('requires a service user and due date', function () {
        actingAs($this->admin);

        livewire(CreateCall::class)
            ->fillForm(['people_id' => null, 'due_at' => null])
            ->call('create')
            ->assertHasFormErrors(['people_id' => 'required', 'due_at' => 'required']);
    });

    it('lets an admin schedule a call for a liaison', function () {
        $serviceUser = People::factory()->create(['team_id' => $this->team->id, 'type' => 'service_user', 'is_service_user' => true]);
        $dueAt = now()->addDays(3)->setTime(10, 0);

        actingAs($this->admin);

        livewire(CreateCall::class)
            ->fillForm([
                'people_id' => $serviceUser->id,
                'assigned_user_id' => $this->liaison->id,
                'due_at' => $dueAt->toDateTimeString(),
                'reason' => 'Check how the new medication is going',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $call = Call::query()->where('people_id', $serviceUser->id)->sole();

        expect($call->team_id)->toBe($this->team->id)
            ->and($call->assigned_user_id)->toBe($this->liaison->id)
            ->and($call->original_due_at->toDateTimeString())->toBe($dueAt->toDateTimeString());

        Notification::assertSentTo($this->liaison, CallAssignedNotification::class);
    });
});

it('shows the call history on the service user record', function () {
    $person = People::factory()->create(['team_id' => $this->team->id, 'type' => 'service_user', 'is_service_user' => true]);
    $serviceUser = ServiceUser::query()->findOrFail($person->id);
    $call = Call::factory()->completed()->create(['team_id' => $this->team->id, 'people_id' => $serviceUser->id]);

    actingAs($this->admin);

    livewire(CallsRelationManager::class, [
        'ownerRecord' => $serviceUser,
        'pageClass' => EditServiceUser::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$call]);
});
