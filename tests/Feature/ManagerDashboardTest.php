<?php

declare(strict_types=1);

use App\Enums\SupportStatus;
use App\Filament\Pages\ManagerDashboard;
use App\Filament\Widgets\ManagerStatsOverview;
use App\Models\People;
use App\Models\ServiceUser;
use App\Models\Team;
use App\Models\User;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

/**
 * @return array{0: User, 1: Team}
 */
function createManagerUser(): array
{
    $team = Team::firstOrCreate(['name' => 'Spinney Hill', 'personal_team' => false, 'user_id' => 1]);
    setPermissionsTeamId($team->id);

    $managerRole = Role::findOrCreate('manager', 'web');

    $manager = User::factory()->create(['current_team_id' => $team->id]);
    $manager->assignRole($managerRole);

    return [$manager, $team];
}

it('allows managers to access manager dashboard', function () {
    [$manager] = createManagerUser();
    actingAs($manager);

    expect(ManagerDashboard::canAccess())->toBeTrue();

    livewire(ManagerDashboard::class)
        ->assertSuccessful();
});

it('denies access to staff without manager role', function () {
    $team = Team::firstOrCreate(['name' => 'Spinney Hill', 'personal_team' => false, 'user_id' => 1]);
    setPermissionsTeamId($team->id);

    $staff = User::factory()->create(['current_team_id' => $team->id]);
    actingAs($staff);

    expect(ManagerDashboard::canAccess())->toBeFalse();
});

it('renders manager stats overview widget', function () {
    [$manager, $team] = createManagerUser();

    actingAs($manager);

    $person = People::factory()->create([
        'type' => 'service_user',
        'is_service_user' => true,
        'team_id' => $team->id,
    ]);
    /** @var ServiceUser $serviceUser */
    $serviceUser = ServiceUser::find($person->id);
    $serviceUser->profile()->create([
        'team_id' => $team->id,
        'support_status' => SupportStatus::NeedsAttention,
    ]);

    livewire(ManagerStatsOverview::class)
        ->assertSuccessful();
});
