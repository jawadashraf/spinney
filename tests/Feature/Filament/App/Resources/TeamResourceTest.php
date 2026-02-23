<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\App\Resources;

use App\Filament\Resources\TeamResource;
use App\Filament\Resources\TeamResource\Pages\ListTeams;
use App\Models\Team;
use App\Models\User;
use Spatie\Permission\Models\Role;
use function Pest\Livewire\livewire;

beforeEach(function () {
    // Ensure super_admin role exists
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    
/** @var User $user */
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->user->assignRole('super_admin');
    $this->actingAs($this->user);
});

it('can render the index page', function (): void {
    livewire(ListTeams::class)
        ->assertOk();
});

it('has correct columns', function (string $column): void {
    livewire(ListTeams::class)
        ->assertTableColumnExists($column);
})->with(['name', 'owner.name', 'personal_team', 'users_count']);

it('can search by name', function (): void {
    $team = Team::factory()->create(['name' => 'Unique Team Name']);
    
    livewire(ListTeams::class)
        ->searchTable('Unique Team Name')
        ->assertCanSeeTableRecords([$team]);
});

it('can see owner name in table', function (): void {
    $owner = User::factory()->create(['name' => 'Team Owner']);
    $team = Team::factory()->create(['user_id' => $owner->id]);
    
    livewire(ListTeams::class)
        ->assertSee('Team Owner');
});

it('excludes service_user from being attached to a team', function (): void {
    $team = Team::factory()->create();
    $serviceUser = User::factory()->create(['name' => 'Service User']);
    
    // Ensure role exists and assign it
    $role = Role::firstOrCreate(['name' => 'service_user', 'guard_name' => 'web']);
    $serviceUser->assignRole($role);
    
    $regularUser = User::factory()->create(['name' => 'Regular User']);
    
    livewire(\App\Filament\Resources\TeamResource\RelationManagers\UsersRelationManager::class, [
        'ownerRecord' => $team,
        'pageClass' => \App\Filament\Resources\TeamResource\Pages\EditTeam::class,
    ])
        ->mountTableAction('attach')
        ->setTableActionData([
            'recordId' => $regularUser->id,
        ])
        ->assertHasNoTableActionErrors()
        ->setTableActionData([
            'recordId' => $serviceUser->id,
        ])
        ->callMountedTableAction()
        ->assertHasTableActionErrors(['recordId']);
});
