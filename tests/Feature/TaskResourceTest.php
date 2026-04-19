<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CustomFields\TaskField;
use App\Enums\TaskType;
use App\Filament\Resources\TaskResource;
use App\Filament\Resources\TaskResource\Pages\ManageTasks;
use App\Models\CustomField;
use App\Models\CustomFieldOption;
use App\Models\Department;
use App\Models\People;
use App\Models\Task;
use App\Models\User;
use App\Models\Team;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->team = Team::where('name', 'Spinney Hill')->first();
    setPermissionsTeamId($this->team->id);
    
    $this->admin = User::where('email', 'admin@test.com')->first();
    $this->admin->current_team_id = $this->team->id;
    $this->admin->save();
    
    $this->liaisonUser = User::where('email', 'liaison@test.com')->first();
    $this->liaisonUser->current_team_id = $this->team->id;
    $this->liaisonUser->save();

    $this->liaisonDept = Department::where('name', 'Liaison')->where('team_id', $this->team->id)->first();
    $this->mgmtDept = Department::where('name', 'Management')->where('team_id', $this->team->id)->first();
    
    actingAs($this->admin);
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
        'type' => 'rich_editor',
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
    it('requires people when task type is follow-up call', function () {
        actingAs($this->liaisonUser);

        livewire(ManageTasks::class)
            ->callAction(CreateAction::class, data: [
                'type' => TaskType::FollowUpCall->value,
                'title' => 'Test Follow-up',
                'people' => [], // Empty
            ])
            ->assertHasActionErrors(['people' => 'required']);
    });

    it('does not require people when task type is general task', function () {
        actingAs($this->liaisonUser);

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

        livewire(ManageTasks::class)
            ->assertActionVisible('recordOutcome', $followUpTask)
            ->assertActionHidden('recordOutcome', $generalTask);
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

        livewire(ManageTasks::class)
            ->callAction('recordOutcome', data: [
                'outcome' => 'Spoke to them, all good.',
                'call_date' => now()->toDateString(),
            ], record: $followUpTask)
            ->assertHasNoActionErrors()
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

        livewire(ManageTasks::class)
            ->filterTable('type', [TaskType::FollowUpCall->value])
            ->assertCanSeeTableRecords([$followUpTask])
            ->assertCanNotSeeTableRecords([$generalTask]);
    });

    it('scopes by due date this week by default', function () {
        $thisWeekTask = Task::factory()->create(['team_id' => $this->team->id, 'title' => 'This Week', 'due_date' => now()->startOfWeek()->addDay()]);
        $nextWeekTask = Task::factory()->create(['team_id' => $this->team->id, 'title' => 'Next Week', 'due_date' => now()->addWeek()]);

        actingAs($this->admin);

        livewire(ManageTasks::class)
            ->assertCanSeeTableRecords([$thisWeekTask])
            ->assertCanNotSeeTableRecords([$nextWeekTask]);
            
        // Toggle filter off
        livewire(ManageTasks::class)
            ->filterTable('due_this_week', false)
            ->assertCanSeeTableRecords([$thisWeekTask, $nextWeekTask]);
    });
});
