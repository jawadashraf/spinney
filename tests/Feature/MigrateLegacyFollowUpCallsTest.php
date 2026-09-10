<?php

declare(strict_types=1);

use App\Enums\CustomFields\TaskField;
use App\Enums\EnquiryDirection;
use App\Enums\EnquiryStatus;
use App\Enums\TaskType;
use App\Models\Call;
use App\Models\CustomField;
use App\Models\CustomFieldOption;
use App\Models\Enquiry;
use App\Models\People;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();
    setPermissionsTeamId($this->team->id);

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

    $this->serviceUser = People::factory()->create([
        'team_id' => $this->team->id,
        'type' => 'service_user',
        'is_service_user' => true,
    ]);
});

it('converts open follow-up call tasks into calls and marks the task done', function () {
    $liaison = User::factory()->create(['current_team_id' => $this->team->id]);
    $relative = People::factory()->create(['team_id' => $this->team->id]);

    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'type' => TaskType::FollowUpCall,
        'title' => 'Check in after discharge',
        'due_date' => now()->addDays(2)->setTime(10, 0),
    ]);
    $task->people()->attach([$this->serviceUser->id, $relative->id]);
    $task->assignees()->attach($liaison->id);

    $this->artisan('calls:migrate-legacy-follow-ups')->assertSuccessful();

    $call = Call::query()->where('task_id', $task->id)->sole();

    expect($call->people_id)->toBe($this->serviceUser->id)
        ->and($call->assigned_user_id)->toBe($liaison->id)
        ->and($call->reason)->toBe('Check in after discharge')
        ->and($call->due_at->toDateTimeString())->toBe($task->due_date->toDateTimeString())
        ->and($task->customFieldValues()->where('custom_field_id', $this->statusField->id)->value('integer_value'))->toBe($this->doneOption->id);

    $this->artisan('calls:migrate-legacy-follow-ups')->assertSuccessful();

    expect(Call::query()->where('task_id', $task->id)->count())->toBe(1);
});

it('converts open outbound enquiries for service users and closes them', function () {
    $enquiry = Enquiry::factory()->create([
        'team_id' => $this->team->id,
        'people_id' => $this->serviceUser->id,
        'direction' => EnquiryDirection::OUTBOUND,
        'status' => EnquiryStatus::OPEN,
        'due_date' => now()->addDay(),
    ]);

    $anonymousEnquiry = Enquiry::factory()->create([
        'team_id' => $this->team->id,
        'direction' => EnquiryDirection::OUTBOUND,
        'status' => EnquiryStatus::OPEN,
    ]);

    $this->artisan('calls:migrate-legacy-follow-ups')->assertSuccessful();

    expect(Call::query()->where('enquiry_id', $enquiry->id)->exists())->toBeTrue()
        ->and($enquiry->fresh()->status)->toBe(EnquiryStatus::CLOSED)
        ->and(Call::query()->where('enquiry_id', $anonymousEnquiry->id)->exists())->toBeFalse()
        ->and($anonymousEnquiry->fresh()->status)->toBe(EnquiryStatus::OPEN);
});

it('saves nothing on a dry run', function () {
    $enquiry = Enquiry::factory()->create([
        'team_id' => $this->team->id,
        'people_id' => $this->serviceUser->id,
        'direction' => EnquiryDirection::OUTBOUND,
        'status' => EnquiryStatus::OPEN,
    ]);

    $this->artisan('calls:migrate-legacy-follow-ups', ['--dry-run' => true])->assertSuccessful();

    expect(Call::query()->count())->toBe(0)
        ->and($enquiry->fresh()->status)->toBe(EnquiryStatus::OPEN);
});
