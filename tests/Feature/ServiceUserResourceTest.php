<?php

declare(strict_types=1);

use App\Enums\TaskType;
use App\Filament\Resources\ServiceUsers\Pages\ListServiceUsers;
use App\Models\People;
use App\Models\ServiceUser;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $volunteerRole = Role::firstOrCreate(['name' => 'volunteer_liaison', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $viewAnyPermission = Permission::firstOrCreate(['name' => 'ViewAny:ServiceUser', 'guard_name' => 'web']);
    $viewPermission = Permission::firstOrCreate(['name' => 'View:ServiceUser', 'guard_name' => 'web']);
    $volunteerRole->givePermissionTo($viewAnyPermission, $viewPermission);

    $this->team = Team::firstOrCreate(['name' => 'Spinney Hill', 'personal_team' => false, 'user_id' => 1]);
    setPermissionsTeamId($this->team->id);
});

it('restricts volunteer liaison to see only assigned service users', function () {
    $volunteer = User::factory()->create(['current_team_id' => $this->team->id]);
    $volunteer->teams()->attach($this->team);
    $volunteer->assignRole('volunteer_liaison');

    $assignedPerson = People::factory()->create(['type' => 'service_user', 'is_service_user' => true]);
    $unassignedPerson = People::factory()->create(['type' => 'service_user', 'is_service_user' => true]);

    $assignedServiceUser = ServiceUser::find($assignedPerson->id);
    $unassignedServiceUser = ServiceUser::find($unassignedPerson->id);

    $task = Task::factory()->create();
    $task->assignees()->attach($volunteer->id);
    $task->people()->attach($assignedServiceUser->id);

    $this->actingAs($volunteer);
    Filament::setTenant($this->team);

    livewire(ListServiceUsers::class)
        ->assertCanSeeTableRecords([$assignedServiceUser])
        ->assertCanNotSeeTableRecords([$unassignedServiceUser]);
});

it('allows admin volunteer liaison to see all service users', function () {
    $volunteer = User::factory()->create(['current_team_id' => $this->team->id]);
    $volunteer->teams()->attach($this->team);
    $volunteer->assignRole('volunteer_liaison');
    $volunteer->assignRole('admin');

    $assignedPerson = People::factory()->create(['type' => 'service_user', 'is_service_user' => true]);
    $unassignedPerson = People::factory()->create(['type' => 'service_user', 'is_service_user' => true]);

    $assignedServiceUser = ServiceUser::find($assignedPerson->id);
    $unassignedServiceUser = ServiceUser::find($unassignedPerson->id);

    $task = Task::factory()->create();
    $task->assignees()->attach($volunteer->id);
    $task->people()->attach($assignedServiceUser->id);

    $this->actingAs($volunteer);
    Filament::setTenant($this->team);

    livewire(ListServiceUsers::class)
        ->assertCanSeeTableRecords([$assignedServiceUser, $unassignedServiceUser]);
});

it('allows volunteer liaison with additional liaison role to see all service users', function () {
    Role::firstOrCreate(['name' => 'liaison', 'guard_name' => 'web']);

    $volunteer = User::factory()->create(['current_team_id' => $this->team->id]);
    $volunteer->teams()->attach($this->team);
    $volunteer->assignRole('volunteer_liaison');
    $volunteer->assignRole('liaison');

    $assignedPerson = People::factory()->create(['type' => 'service_user', 'is_service_user' => true]);
    $unassignedPerson = People::factory()->create(['type' => 'service_user', 'is_service_user' => true]);

    $assignedServiceUser = ServiceUser::find($assignedPerson->id);
    $unassignedServiceUser = ServiceUser::find($unassignedPerson->id);

    $task = Task::factory()->create();
    $task->assignees()->attach($volunteer->id);
    $task->people()->attach($assignedServiceUser->id);

    $this->actingAs($volunteer);
    Filament::setTenant($this->team);

    livewire(ListServiceUsers::class)
        ->assertCanSeeTableRecords([$assignedServiceUser, $unassignedServiceUser]);
});

it('hides service users linked to tasks assigned to a different volunteer', function () {
    $volunteer = User::factory()->create(['current_team_id' => $this->team->id]);
    $volunteer->teams()->attach($this->team);
    $volunteer->assignRole('volunteer_liaison');

    $otherVolunteer = User::factory()->create(['current_team_id' => $this->team->id]);
    $otherVolunteer->teams()->attach($this->team);
    $otherVolunteer->assignRole('volunteer_liaison');

    $assignedPerson = People::factory()->create(['type' => 'service_user', 'is_service_user' => true]);
    $otherPerson = People::factory()->create(['type' => 'service_user', 'is_service_user' => true]);

    $assignedServiceUser = ServiceUser::find($assignedPerson->id);
    $otherServiceUser = ServiceUser::find($otherPerson->id);

    $task = Task::factory()->create();
    $task->assignees()->attach($volunteer->id);
    $task->people()->attach($assignedServiceUser->id);

    $otherTask = Task::factory()->create();
    $otherTask->assignees()->attach($otherVolunteer->id);
    $otherTask->people()->attach($otherServiceUser->id);

    $this->actingAs($volunteer);
    Filament::setTenant($this->team);

    livewire(ListServiceUsers::class)
        ->assertCanSeeTableRecords([$assignedServiceUser])
        ->assertCanNotSeeTableRecords([$otherServiceUser]);
});

it('shows service users linked through any task type', function () {
    $volunteer = User::factory()->create(['current_team_id' => $this->team->id]);
    $volunteer->teams()->attach($this->team);
    $volunteer->assignRole('volunteer_liaison');

    $generalTaskPerson = People::factory()->create(['type' => 'service_user', 'is_service_user' => true]);
    $followUpCallPerson = People::factory()->create(['type' => 'service_user', 'is_service_user' => true]);

    $generalTaskServiceUser = ServiceUser::find($generalTaskPerson->id);
    $followUpCallServiceUser = ServiceUser::find($followUpCallPerson->id);

    $generalTask = Task::factory()->create(['type' => TaskType::GeneralTask]);
    $generalTask->assignees()->attach($volunteer->id);
    $generalTask->people()->attach($generalTaskServiceUser->id);

    $followUpCallTask = Task::factory()->create(['type' => TaskType::FollowUpCall]);
    $followUpCallTask->assignees()->attach($volunteer->id);
    $followUpCallTask->people()->attach($followUpCallServiceUser->id);

    $this->actingAs($volunteer);
    Filament::setTenant($this->team);

    livewire(ListServiceUsers::class)
        ->assertCanSeeTableRecords([$generalTaskServiceUser, $followUpCallServiceUser]);
});

it('allows volunteer liaison to open the view action for an assigned service user', function () {
    $volunteer = User::factory()->create(['current_team_id' => $this->team->id]);
    $volunteer->teams()->attach($this->team);
    $volunteer->assignRole('volunteer_liaison');

    $person = People::factory()->create(['type' => 'service_user', 'is_service_user' => true]);
    $serviceUser = ServiceUser::find($person->id);

    $task = Task::factory()->create();
    $task->assignees()->attach($volunteer->id);
    $task->people()->attach($serviceUser->id);

    $this->actingAs($volunteer);
    Filament::setTenant($this->team);

    livewire(ListServiceUsers::class)
        ->callAction(TestAction::make('view')->table($serviceUser))
        ->assertOk();
});
