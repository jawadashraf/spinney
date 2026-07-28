<?php

declare(strict_types=1);

use App\Filament\Resources\Schedules\ScheduleResource;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('volunteer cannot view ScheduleResource', function () {
    $team = Team::factory()->create();
    setPermissionsTeamId($team->id);
    Filament\Facades\Filament::setCurrentPanel(Filament\Facades\Filament::getPanel('app'));

    Permission::firstOrCreate(['name' => 'ViewAny:Schedule', 'guard_name' => 'web']);

    $volunteerRole = Role::firstOrCreate([
        'name' => 'volunteer_liaison',
        'guard_name' => 'web',
        'team_id' => $team->id,
    ]);

    $volunteerRole->givePermissionTo('ViewAny:Schedule');

    $volunteer = User::factory()->create();
    $volunteer->teams()->attach($team);
    $volunteer->assignRole($volunteerRole);

    $this->actingAs($volunteer);

    $response = $this->get(ScheduleResource::getUrl('index'));
    $response->assertForbidden();
});
