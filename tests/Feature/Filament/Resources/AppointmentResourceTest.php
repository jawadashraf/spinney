<?php

declare(strict_types=1);

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Appointments\Schemas\AppointmentForm;
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
});

it('can calculate and save appointment details on booking mutations for appointment resource', function () {
    $inputData = [
        'booking_date' => '2026-05-19',
        'selected_slot' => '10:00-11:00',
        'metadata' => [
            'attendee_type' => 'external',
            'external_attendee_name' => 'John Doe',
        ],
    ];

    $savedData = AppointmentForm::mutateFormDataBeforeSave($inputData);

    expect($savedData['start_date'])->toBe('2026-05-19')
        ->and($savedData['end_date'])->toBe('2026-05-19')
        ->and($savedData['metadata']['start_time'])->toBe('10:00')
        ->and($savedData['metadata']['end_time'])->toBe('11:00')
        ->and($savedData['is_recurring'])->toBeFalse()
        ->and($savedData['frequency'])->toBeNull()
        ->and($savedData['frequency_config'])->toBeNull();
});

it('can fill appointment booking date and slot from records for appointment resource', function () {
    $schedule = new Schedule([
        'schedule_type' => 'appointment',
        'start_date' => '2026-05-20',
        'end_date' => '2026-05-20',
        'is_recurring' => false,
        'metadata' => [
            'start_time' => '15:00',
            'end_time' => '16:00',
        ],
    ]);

    $formState = AppointmentForm::fillFormFromRecord($schedule);

    expect($formState['booking_date'])->toBe('2026-05-20')
        ->and($formState['selected_slot'])->toBe('15:00-16:00')
        ->and($formState['is_recurring'])->toBeFalse();
});

it('authorizes resource actions based on custom appointment permissions', function () {
    $userWithoutPermissions = User::factory()->create(['current_team_id' => $this->team->id]);
    $userWithoutPermissions->syncPermissions([]);

    actingAs($userWithoutPermissions);

    // Import AppointmentResource FQCN
    $resource = AppointmentResource::class;

    expect($resource::canViewAny())->toBeFalse()
        ->and($resource::canCreate())->toBeFalse();

    $permissionViewAny = Permission::firstOrCreate(['name' => 'ViewAny:Appointment', 'guard_name' => 'web']);
    $permissionCreate = Permission::firstOrCreate(['name' => 'Create:Appointment', 'guard_name' => 'web']);

    $userWithPermissions = User::factory()->create(['current_team_id' => $this->team->id]);
    $userWithPermissions->givePermissionTo($permissionViewAny);
    $userWithPermissions->givePermissionTo($permissionCreate);

    actingAs($userWithPermissions);

    expect($resource::canViewAny())->toBeTrue()
        ->and($resource::canCreate())->toBeTrue()
        ->and($resource::canEdit(new Schedule))->toBeFalse();
});
