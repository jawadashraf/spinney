<?php

declare(strict_types=1);

use App\Filament\Resources\Schedules\Schemas\ScheduleForm;
use App\Models\People;
use App\Models\Schedule;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->manager = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->manager->assignRole('manager');

    // Grant schedules permissions for testing
    $permissions = [
        'ViewAny:Schedule',
        'View:Schedule',
        'Create:Schedule',
        'Update:Schedule',
        'Delete:Schedule',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        $this->manager->givePermissionTo($permission);
    }

    actingAs($this->manager);
    Filament::setTenant($this->team);

    $this->serviceUser = People::factory()->create(['is_service_user' => true, 'team_id' => $this->team->id]);
});

it('can calculate daily recurrence save data', function () {
    $inputData = [
        'is_recurring' => true,
        'repeat_interval' => 1,
        'repeat_unit' => 'day',
        'start_date' => '2026-05-18 10:00:00',
        'end_type' => 'never',
    ];

    $savedData = ScheduleForm::mutateFormDataBeforeSave($inputData);

    expect($savedData['frequency'])->toBe('daily')
        ->and($savedData['frequency_config'])->toBeEmpty();
});

it('can calculate weekly custom recurrence save data', function () {
    $inputData = [
        'is_recurring' => true,
        'repeat_interval' => 3,
        'repeat_unit' => 'week',
        'days_of_week' => ['monday', 'wednesday', 'friday'],
        'start_date' => '2026-05-18 10:00:00',
        'end_type' => 'never',
    ];

    $savedData = ScheduleForm::mutateFormDataBeforeSave($inputData);

    expect($savedData['frequency'])->toBe('every_3_weeks')
        ->and($savedData['frequency_config']['days'])->toBe(['monday', 'wednesday', 'friday'])
        ->and($savedData['frequency_config']['startsOn'])->toBe('2026-05-18');
});

it('can calculate monthly ordinal weekday recurrence save data', function () {
    $inputData = [
        'is_recurring' => true,
        'repeat_interval' => 1,
        'repeat_unit' => 'month',
        'month_repeat_by' => 'day_of_week',
        'ordinal' => 2, // Second
        'day_of_week_name' => 'tuesday', // Tuesday
        'start_date' => '2026-05-18 10:00:00',
        'end_type' => 'never',
    ];

    $savedData = ScheduleForm::mutateFormDataBeforeSave($inputData);

    expect($savedData['frequency'])->toBe('monthly_ordinal_weekday')
        ->and($savedData['frequency_config']['ordinal'])->toBe(2)
        ->and($savedData['frequency_config']['dayOfWeek'])->toBe(2)
        ->and($savedData['frequency_config']['day'])->toBe('tuesday');
});

it('can calculate calculated end date from occurrences limit', function () {
    $inputData = [
        'is_recurring' => true,
        'repeat_interval' => 1,
        'repeat_unit' => 'week',
        'days_of_week' => ['monday'],
        'start_date' => '2026-05-18 10:00:00', // A Monday
        'end_type' => 'after_occurrences',
        'occurrences' => 5,
    ];

    $savedData = ScheduleForm::mutateFormDataBeforeSave($inputData);

    // 5 occurrences starting on Monday May 18:
    // 1st: May 18
    // 2nd: May 25
    // 3rd: Jun 1
    // 4th: Jun 8
    // 5th: Jun 15
    expect($savedData['end_date'])->toBe('2026-06-15')
        ->and($savedData['metadata']['end_type'])->toBe('after_occurrences')
        ->and($savedData['metadata']['occurrences'])->toBe(5);
});

it('can populate form state from a daily recurring schedule record', function () {
    $schedule = new Schedule([
        'is_recurring' => true,
        'frequency' => 'daily',
        'frequency_config' => [],
        'metadata' => [],
    ]);

    $formState = ScheduleForm::fillFormFromRecord($schedule);

    expect($formState['is_recurring'])->toBeTrue()
        ->and($formState['repeat_unit'])->toBe('day')
        ->and($formState['repeat_interval'])->toBe(1)
        ->and($formState['end_type'])->toBe('never');
});

it('can populate form state from an after occurrences schedule record', function () {
    $schedule = new Schedule([
        'is_recurring' => true,
        'frequency' => 'weekly',
        'frequency_config' => ['days' => ['monday', 'friday']],
        'metadata' => [
            'end_type' => 'after_occurrences',
            'occurrences' => 8,
        ],
    ]);

    $formState = ScheduleForm::fillFormFromRecord($schedule);

    expect($formState['is_recurring'])->toBeTrue()
        ->and($formState['repeat_unit'])->toBe('week')
        ->and($formState['repeat_interval'])->toBe(1)
        ->and($formState['days_of_week'])->toBe(['monday', 'friday'])
        ->and($formState['end_type'])->toBe('after_occurrences')
        ->and($formState['occurrences'])->toBe(8);
});
