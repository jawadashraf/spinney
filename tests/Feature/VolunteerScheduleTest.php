<?php

declare(strict_types=1);

use App\Filament\Resources\MySchedules\Pages\CreateMySchedule;
use App\Filament\Resources\MySchedules\Pages\EditMySchedule;
use App\Filament\Resources\MySchedules\Pages\ListMySchedules;
use App\Models\Schedule;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Zap\Enums\ScheduleTypes;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('app'));
    $this->team = Team::factory()->create();
    setPermissionsTeamId($this->team->id);

    $this->volunteerRole = Role::firstOrCreate([
        'name' => 'volunteer_liaison',
        'guard_name' => 'web',
        'team_id' => $this->team->id,
    ]);

    $this->adminRole = Role::firstOrCreate([
        'name' => 'admin',
        'guard_name' => 'web',
        'team_id' => $this->team->id,
    ]);

    Permission::firstOrCreate(['name' => 'ViewAny:Schedule', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'View:Schedule', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'Create:Schedule', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'Update:Schedule', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'Delete:Schedule', 'guard_name' => 'web']);

    $this->volunteerRole->givePermissionTo([
        'ViewAny:Schedule',
        'View:Schedule',
        'Create:Schedule',
        'Update:Schedule',
        'Delete:Schedule',
    ]);

    Carbon::setTestNow(Carbon::parse('2026-07-28 11:00:00', 'Europe/London'));
});

test('volunteer can view only their own schedules in MyScheduleResource', function (): void {
    $volunteer = User::factory()->create();
    $volunteer->teams()->attach($this->team);
    $volunteer->assignRole($this->volunteerRole);

    $otherUser = User::factory()->create();
    $otherUser->teams()->attach($this->team);

    $mySchedule = Schedule::create([
        'team_id' => $this->team->id,
        'schedulable_type' => (new User)->getMorphClass(),
        'schedulable_id' => $volunteer->id,
        'name' => 'My Volunteer Shift',
        'schedule_type' => ScheduleTypes::AVAILABILITY->value,
        'start_date' => '2026-01-01',
        'is_recurring' => false,
        'is_active' => true,
    ]);

    $otherSchedule = Schedule::create([
        'team_id' => $this->team->id,
        'schedulable_type' => (new User)->getMorphClass(),
        'schedulable_id' => $otherUser->id,
        'name' => 'Other User Shift',
        'schedule_type' => ScheduleTypes::AVAILABILITY->value,
        'start_date' => '2026-01-01',
        'is_recurring' => false,
        'is_active' => true,
    ]);

    $this->actingAs($volunteer);

    livewire(ListMySchedules::class)
        ->assertCanSeeTableRecords([$mySchedule])
        ->assertCanNotSeeTableRecords([$otherSchedule]);
});

test('volunteer creating schedule automatically sets schedulable to self and is_approved to false', function (): void {
    $volunteer = User::factory()->create();
    $volunteer->teams()->attach($this->team);
    $volunteer->assignRole($this->volunteerRole);

    $this->actingAs($volunteer);

    livewire(CreateMySchedule::class)
        ->fillForm([
            'name' => 'New Requested Shift',
            'schedule_type' => ScheduleTypes::AVAILABILITY->value,
            'start_date' => '2026-08-01',
            'is_recurring' => false,
            'is_active' => true,
            'periods' => [
                [
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $schedule = Schedule::where('name', 'New Requested Shift')->first();
    expect($schedule)->not()->toBeNull();
    expect($schedule->schedulable_id)->toBe($volunteer->id);
    expect($schedule->schedulable_type)->toBe((new User)->getMorphClass());
    expect($schedule->metadata['is_approved'] ?? null)->toBeFalse();
});

test('volunteer editing schedule resets is_approved to false', function (): void {
    $volunteer = User::factory()->create();
    $volunteer->teams()->attach($this->team);
    $volunteer->assignRole($this->volunteerRole);

    $schedule = Schedule::create([
        'team_id' => $this->team->id,
        'schedulable_type' => (new User)->getMorphClass(),
        'schedulable_id' => $volunteer->id,
        'name' => 'My Approved Shift',
        'schedule_type' => ScheduleTypes::AVAILABILITY->value,
        'start_date' => '2026-01-01',
        'is_recurring' => false,
        'is_active' => true,
        'metadata' => ['is_approved' => true],
    ]);

    $schedule->periods()->create([
        'date' => '2026-01-01',
        'start_time' => '09:00',
        'end_time' => '17:00',
        'is_available' => true,
    ]);

    $this->actingAs($volunteer);

    livewire(EditMySchedule::class, [
        'record' => $schedule->getRouteKey(),
    ])
        ->fillForm([
            'name' => 'Updated Approved Shift',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $schedule->refresh();
    expect($schedule->name)->toBe('Updated Approved Shift');
    expect($schedule->metadata['is_approved'])->toBeFalse();
});

test('isWithinWorkHours checks is_approved flag', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-07-28 12:00:00', 'Europe/London'));

    $volunteer = User::factory()->create();
    $volunteer->teams()->attach($this->team);
    $volunteer->assignRole($this->volunteerRole);

    $schedule = Schedule::create([
        'team_id' => $this->team->id,
        'schedulable_type' => 'user',
        'schedulable_id' => $volunteer->id,
        'name' => 'Pending Shift',
        'schedule_type' => ScheduleTypes::AVAILABILITY->value,
        'start_date' => '2026-07-28',
        'is_recurring' => false,
        'is_active' => true,
        'metadata' => ['is_approved' => false],
    ]);

    $schedule->periods()->create([
        'date' => '2026-07-28',
        'start_time' => '00:00',
        'end_time' => '23:59',
        'is_available' => true,
    ]);

    expect($volunteer->isWithinWorkHours())->toBeFalse();

    $schedule->update(['metadata' => ['is_approved' => true]]);
    $volunteer = $volunteer->fresh();
    $volunteer->unsetRelation('schedules');
    expect($volunteer->isWithinWorkHours())->toBeTrue();

    Carbon::setTestNow();
});
