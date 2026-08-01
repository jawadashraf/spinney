<?php

declare(strict_types=1);

use App\Enums\CustomFieldType;
use App\Models\CustomField;
use App\Models\CustomFieldOption;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;

/**
 * @return array{0: User, 1: Team}
 */
function createTaskUser(): array
{
    $team = Team::firstOrCreate(['name' => 'Spinney Hill', 'personal_team' => false, 'user_id' => 1]);
    setPermissionsTeamId($team->id);

    $user = User::factory()->create(['current_team_id' => $team->id]);

    return [$user, $team];
}

it('logs activity when task is created or updated', function () {
    [$user, $team] = createTaskUser();
    actingAs($user);

    $task = Task::factory()->create([
        'team_id' => $team->id,
        'title' => 'Initial Task Title',
    ]);

    $task->update([
        'title' => 'Updated Task Title',
    ]);

    $activity = Activity::forSubject($task)->latest()->first();

    expect($activity)->not->toBeNull()
        ->and($activity->subject_id)->toBe($task->id)
        ->and($activity->subject_type)->toBe($task->getMorphClass());
});

it('logs task completion event with causer and timestamp', function () {
    [$user, $team] = createTaskUser();
    actingAs($user);

    $task = Task::factory()->create([
        'team_id' => $team->id,
        'title' => 'Complete me task',
    ]);

    activity()
        ->performedOn($task)
        ->causedBy($user)
        ->event('completed')
        ->log("Task '{$task->title}' marked as Done");

    $completionActivity = Activity::forSubject($task)
        ->where('event', 'completed')
        ->latest()
        ->first();

    expect($completionActivity)->not->toBeNull()
        ->and($completionActivity->causer_id)->toBe($user->id)
        ->and($completionActivity->description)->toContain('marked as Done');
});

it('automatically logs activity when custom field status is saved as Done', function () {
    [$user, $team] = createTaskUser();
    actingAs($user);

    $statusField = CustomField::firstOrCreate([
        'code' => 'status',
        'entity_type' => Task::class,
    ], [
        'name' => 'Status',
        'type' => CustomFieldType::SELECT->value,
    ]);

    $doneOption = CustomFieldOption::firstOrCreate([
        'custom_field_id' => $statusField->id,
        'name' => 'Done',
    ]);

    $task = Task::factory()->create([
        'team_id' => $team->id,
        'title' => 'Custom Field Status Task',
    ]);

    $task->saveCustomFieldValue($statusField, $doneOption->id);

    $completionActivity = Activity::forSubject($task)
        ->where('event', 'completed')
        ->latest()
        ->first();

    expect($completionActivity)->not->toBeNull()
        ->and($completionActivity->description)->toBe('Task marked as Done');
});
