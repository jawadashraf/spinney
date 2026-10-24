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

use App\Filament\Widgets\CallStatsOverview;
use App\Filament\Widgets\CounsellorSessionsWidget;
use App\Filament\Widgets\OverdueCallsWidget;
use App\Filament\Widgets\SafeguardingAlertsWidget;
use App\Filament\Widgets\ServiceUsersNeedingSupport;

it('renders manager stats overview widget and includes target resource urls', function () {
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

    $component = livewire(ManagerStatsOverview::class)
        ->assertSuccessful();

    $stats = (fn () => $this->getCachedStats())->call($component->instance());
    expect($stats)->toHaveCount(4);
    expect($stats[0]->getUrl())->toContain('tableFilters');
    expect($stats[0]->getUrl())->toContain('needing_attention');
    expect($stats[1]->getUrl())->toContain('tableFilters');
    expect($stats[1]->getUrl())->toContain('safeguarding_flags');
    expect($stats[2]->getUrl())->toContain('tableFilters');
    expect($stats[2]->getUrl())->toContain('overdue');
    expect($stats[3]->getUrl())->toContain('tableFilters');
    expect($stats[3]->getUrl())->toContain('active');
});

it('renders call stats overview widget with tab navigation urls', function () {
    [$manager] = createManagerUser();
    actingAs($manager);

    $component = livewire(CallStatsOverview::class)
        ->assertSuccessful();

    $stats = (fn () => $this->getCachedStats())->call($component->instance());
    expect($stats)->toHaveCount(5);
    expect($stats[0]->getUrl())->toContain('activeTab=today');
    expect($stats[1]->getUrl())->toContain('activeTab=overdue');
    expect($stats[2]->getUrl())->toContain('activeTab=history');
});

it('renders safeguarding alerts widget with filtered urls', function () {
    [$manager] = createManagerUser();
    actingAs($manager);

    $component = livewire(SafeguardingAlertsWidget::class)
        ->assertSuccessful();

    $stats = (fn () => $this->getCachedStats())->call($component->instance());
    expect($stats)->toHaveCount(2);
    expect($stats[0]->getUrl())->toContain('tableFilters');
    expect($stats[0]->getUrl())->toContain('safeguarding_flags');
    expect($stats[1]->getUrl())->toContain('tableFilters');
    expect($stats[1]->getUrl())->toContain('emergency');
});

it('renders manager dashboard table widgets successfully', function () {
    [$manager] = createManagerUser();
    actingAs($manager);

    livewire(ServiceUsersNeedingSupport::class)->assertSuccessful();
    livewire(OverdueCallsWidget::class)->assertSuccessful();
    livewire(CounsellorSessionsWidget::class)->assertSuccessful();
});
