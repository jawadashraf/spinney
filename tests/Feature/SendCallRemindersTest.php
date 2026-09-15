<?php

declare(strict_types=1);

use App\Enums\CallStatus;
use App\Enums\CustomFields\TaskField;
use App\Models\Call;
use App\Models\CallPlan;
use App\Models\CustomField;
use App\Models\CustomFieldOption;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Notifications\CallPlansWithoutCallsNotification;
use App\Notifications\CallsDueDigestNotification;
use App\Notifications\CallsOverdueDigestNotification;
use App\Notifications\TaskDueReminderNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();
    setPermissionsTeamId($this->team->id);

    $this->liaison = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->liaison->assignRole('liaison');
});

it('sends each liaison a digest of calls due today and overdue', function () {
    Call::factory()->dueToday()->assignedTo($this->liaison)->create(['team_id' => $this->team->id]);
    Call::factory()->overdue()->assignedTo($this->liaison)->create(['team_id' => $this->team->id]);
    Call::factory()->completed()->overdue()->assignedTo($this->liaison)->create(['team_id' => $this->team->id]);
    Call::factory()->assignedTo($this->liaison)->create(['team_id' => $this->team->id, 'due_at' => now()->addDays(3)]);

    $this->artisan('calls:send-reminders')->assertSuccessful();

    Notification::assertSentTo(
        $this->liaison,
        CallsDueDigestNotification::class,
        fn (CallsDueDigestNotification $notification): bool => $notification->dueToday === 1 && $notification->overdue === 1,
    );
});

it('does not notify liaisons with nothing due', function () {
    Call::factory()->assignedTo($this->liaison)->create(['team_id' => $this->team->id, 'due_at' => now()->addDays(3)]);

    $this->artisan('calls:send-reminders')->assertSuccessful();

    Notification::assertNotSentTo($this->liaison, CallsDueDigestNotification::class);
});

it('sends managers a summary of long overdue calls grouped by liaison', function () {
    $manager = User::factory()->create(['current_team_id' => $this->team->id]);
    $manager->assignRole('manager');

    Call::factory()->assignedTo($this->liaison)->create(['team_id' => $this->team->id, 'due_at' => now()->subDays(3)]);
    Call::factory()->create(['team_id' => $this->team->id, 'due_at' => now()->subDays(4)]);
    Call::factory()->assignedTo($this->liaison)->create(['team_id' => $this->team->id, 'due_at' => now()->subHours(3)]);

    $this->artisan('calls:send-reminders')->assertSuccessful();

    Notification::assertSentTo(
        $manager,
        CallsOverdueDigestNotification::class,
        fn (CallsOverdueDigestNotification $notification): bool => ($notification->overdueByLiaison[$this->liaison->name] ?? 0) === 1
            && ($notification->overdueByLiaison['Unassigned'] ?? 0) === 1,
    );
});

it('alerts managers to active call plans with no call booked', function () {
    $manager = User::factory()->create(['current_team_id' => $this->team->id]);
    $manager->assignRole('manager');

    $planWithoutCall = CallPlan::factory()->create(['team_id' => $this->team->id]);
    $planWithoutCall->calls()->update(['status' => CallStatus::Cancelled]);

    CallPlan::factory()->create(['team_id' => $this->team->id]);

    $endedPlan = CallPlan::factory()->create([
        'team_id' => $this->team->id,
        'starts_on' => today()->subMonth(),
        'ends_on' => today()->subDay(),
    ]);
    $endedPlan->calls()->update(['status' => CallStatus::Cancelled]);

    $this->artisan('calls:send-reminders')->assertSuccessful();

    Notification::assertSentTo(
        $manager,
        CallPlansWithoutCallsNotification::class,
        fn (CallPlansWithoutCallsNotification $notification): bool => $notification->serviceUserNames === [$planWithoutCall->serviceUser->name],
    );
});

it('does not send task due reminders for tasks marked done', function () {
    $statusField = CustomField::factory()->create([
        'team_id' => $this->team->id,
        'code' => TaskField::STATUS->value,
        'entity_type' => Task::class,
        'type' => 'select',
    ]);

    $doneOption = CustomFieldOption::factory()->create([
        'team_id' => $this->team->id,
        'custom_field_id' => $statusField->id,
        'name' => 'Done',
    ]);

    $openTask = Task::factory()->create(['team_id' => $this->team->id, 'due_date' => now()->subDay()]);
    $doneTask = Task::factory()->create(['team_id' => $this->team->id, 'due_date' => now()->subDay()]);
    $doneTask->saveCustomFieldValue($statusField, (string) $doneOption->id);

    $openTask->assignees()->attach($this->liaison->id);
    $doneTask->assignees()->attach($this->liaison->id);

    $this->artisan('tasks:send-due-reminders')->assertSuccessful();

    Notification::assertSentTo(
        $this->liaison,
        TaskDueReminderNotification::class,
        fn (TaskDueReminderNotification $notification): bool => $notification->task->is($openTask),
    );

    Notification::assertNotSentTo(
        $this->liaison,
        TaskDueReminderNotification::class,
        fn (TaskDueReminderNotification $notification): bool => $notification->task->is($doneTask),
    );
});
