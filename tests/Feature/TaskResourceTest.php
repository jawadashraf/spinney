<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CustomFields\TaskField;
use App\Enums\TaskType;
use App\Filament\Resources\TaskResource\Pages\ManageTasks;
use App\Models\CustomField;
use App\Models\CustomFieldOption;
use App\Models\Department;
use App\Models\People;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->team = Team::firstOrCreate(['name' => 'Spinney Hill', 'personal_team' => false, 'user_id' => 1]);
    setPermissionsTeamId($this->team->id);

    $this->admin = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->admin->assignRole('admin');

    $this->liaisonUser = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->liaisonUser->assignRole('liaison');

    $this->liaisonDept = Department::firstOrCreate(['name' => 'Liaison', 'team_id' => $this->team->id]);
    $this->mgmtDept = Department::firstOrCreate(['name' => 'Management', 'team_id' => $this->team->id]);

    actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($this->team);

    // Provision basic custom fields for the team
    $this->statusField = CustomField::factory()->create([
        'team_id' => $this->team->id,
        'code' => TaskField::STATUS->value,
        'entity_type' => Task::class,
        'type' => 'select',
    ]);

    $this->doneOption = CustomFieldOption::factory()->create([
        'team_id' => $this->team->id,
        'custom_field_id' => $this->statusField->id,
        'name' => 'Done',
    ]);

    $this->callNotesField = CustomField::factory()->create([
        'team_id' => $this->team->id,
        'code' => TaskField::CALL_NOTES->value,
        'entity_type' => Task::class,
        'type' => 'rich-editor',
    ]);
});

describe('Authorization & Scoping', function () {
    it('liaison user can only see tasks from their department or assigned to them', function () {
        $liaisonTask = Task::factory()->create([
            'team_id' => $this->team->id,
            'department_id' => $this->liaisonDept->id,
            'title' => 'Liaison Task',
        ]);

        $mgmtTask = Task::factory()->create([
            'team_id' => $this->team->id,
            'department_id' => $this->mgmtDept->id,
            'title' => 'Mgmt Task',
        ]);

        $unassignedTask = Task::factory()->create([
            'team_id' => $this->team->id,
            'department_id' => $this->liaisonDept->id, // Open in their dept
            'title' => 'Open Liaison Task',
        ]);

        actingAs($this->liaisonUser);
        Filament::setTenant($this->team);

        livewire(ManageTasks::class)
            ->assertCanSeeTableRecords([$liaisonTask, $unassignedTask])
            ->assertCanNotSeeTableRecords([$mgmtTask]);
    });

    it('admin user can see all tasks regardless of department', function () {
        $liaisonTask = Task::factory()->create([
            'team_id' => $this->team->id,
            'department_id' => $this->liaisonDept->id,
            'title' => 'Liaison Task',
        ]);

        $mgmtTask = Task::factory()->create([
            'team_id' => $this->team->id,
            'department_id' => $this->mgmtDept->id,
            'title' => 'Mgmt Task',
        ]);

        actingAs($this->admin);
        Filament::setTenant($this->team);

        livewire(ManageTasks::class)
            ->assertCanSeeTableRecords([$liaisonTask, $mgmtTask]);
    });
});

describe('Validation', function () {
    it('requires people when task type is follow-up call for admin or manager', function () {
        actingAs($this->admin);
        Filament::setTenant($this->team);

        livewire(ManageTasks::class)
            ->callAction(CreateAction::class, data: [
                'type' => TaskType::FollowUpCall->value,
                'title' => 'Test Follow-up',
                'people' => [], // Empty
            ])
            ->assertHasActionErrors(['people' => 'required']);
    });

    it('does not require people when task type is general task', function () {
        actingAs($this->admin);
        Filament::setTenant($this->team);

        livewire(ManageTasks::class)
            ->callAction(CreateAction::class, data: [
                'type' => TaskType::GeneralTask->value,
                'title' => 'Test General',
                'people' => [],
            ])
            ->assertHasNoActionErrors(['people']);
    });
});

describe('Record Outcome Action', function () {
    it('is visible only for follow-up call tasks', function () {
        $generalTask = Task::factory()->create([
            'team_id' => $this->team->id,
            'type' => TaskType::GeneralTask,
        ]);

        $followUpTask = Task::factory()->create([
            'team_id' => $this->team->id,
            'type' => TaskType::FollowUpCall,
        ]);

        actingAs($this->admin);
        Filament::setTenant($this->team);

        livewire(ManageTasks::class)
            ->assertTableActionVisible('recordOutcome', $followUpTask)
            ->assertTableActionHidden('recordOutcome', $generalTask);
    });

    it('creates notes on linked people and marks task as done', function () {
        $person1 = People::factory()->create(['team_id' => $this->team->id]);
        $person2 = People::factory()->create(['team_id' => $this->team->id]);

        $followUpTask = Task::factory()->create([
            'team_id' => $this->team->id,
            'type' => TaskType::FollowUpCall,
            'title' => 'Monthly Check-in',
        ]);
        $followUpTask->people()->attach([$person1->id, $person2->id]);

        actingAs($this->admin);
        Filament::setTenant($this->team);

        livewire(ManageTasks::class)
            ->callTableAction('recordOutcome', $followUpTask, data: [
                'outcome' => 'Spoke to them, all good.',
                'call_date' => now()->toDateString(),
            ])
            ->assertHasNoTableActionErrors()
            ->assertNotified();

        // Verify notes created
        expect($person1->notes()->count())->toBe(1);
        expect($person1->notes()->first()->body)->toBe('Spoke to them, all good.');
        expect($person2->notes()->count())->toBe(1);

        // Verify task status updated to Done
        $statusValue = $followUpTask->customFieldValues()
            ->where('custom_field_id', $this->statusField->id)
            ->first()
            ?->getValue();

        expect((string) $statusValue)->toBe((string) $this->doneOption->id);

        // Verify CALL_NOTES custom field updated
        $notesValue = $followUpTask->customFieldValues()
            ->where('custom_field_id', $this->callNotesField->id)
            ->first()
            ?->getValue();

        expect($notesValue)->toBe('Spoke to them, all good.');
    });
});

describe('Filters', function () {
    it('scopes by type', function () {
        $generalTask = Task::factory()->create(['team_id' => $this->team->id, 'type' => TaskType::GeneralTask]);
        $followUpTask = Task::factory()->create(['team_id' => $this->team->id, 'type' => TaskType::FollowUpCall]);

        actingAs($this->admin);
        Filament::setTenant($this->team);

        livewire(ManageTasks::class)
            ->filterTable('type', [TaskType::FollowUpCall->value])
            ->assertCanSeeTableRecords([$followUpTask])
            ->assertCanNotSeeTableRecords([$generalTask]);
    });

    it('scopes by due date this week by default', function () {
        $thisWeekTask = Task::factory()->create(['team_id' => $this->team->id, 'title' => 'This Week', 'due_date' => now()->startOfWeek()->addDay()]);
        $nextWeekTask = Task::factory()->create(['team_id' => $this->team->id, 'title' => 'Next Week', 'due_date' => now()->addWeek()]);

        actingAs($this->admin);
        Filament::setTenant($this->team);

        livewire(ManageTasks::class)
            ->assertCanSeeTableRecords([$thisWeekTask])
            ->assertCanNotSeeTableRecords([$nextWeekTask]);

        // Toggle filter off
        livewire(ManageTasks::class)
            ->filterTable('due_this_week', false)
            ->assertCanSeeTableRecords([$thisWeekTask, $nextWeekTask]);
    });
});
