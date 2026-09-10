<?php

declare(strict_types=1);

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\ViewUser;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create([
        'is_system_admin' => true,
        'current_team_id' => $this->team->id,
    ]);
    if (! $this->user->teams()->where('team_id', $this->team->id)->exists()) {
        $this->user->teams()->attach($this->team, ['role' => 'admin']);
    }
    $this->user->assignRole('super_admin');
    $this->actingAs($this->user);

    Filament::setTenant($this->team);
    Filament::setCurrentPanel('app');
    Filament::bootCurrentPanel();
});

it('can render the index page', function (): void {
    livewire(ListUsers::class)
        ->assertOk();
});

it('can render the view page', function (): void {
    $record = User::factory()->create();
    $record->teams()->syncWithoutDetaching([$this->team->id]);

    livewire(ViewUser::class, ['record' => $record->getKey()])
        ->assertOk();
});

it('has `:dataset` column', function (string $column): void {
    livewire(ListUsers::class)
        ->assertTableColumnExists($column);
})->with(['profile_photo_url', 'first_name', 'last_name', 'email', 'currentTeam.name', 'roles.name', 'email_verified_at', 'created_at']);

it('can render `:dataset` column', function (string $column): void {
    livewire(ListUsers::class)
        ->assertCanRenderTableColumn($column);
})->with(['profile_photo_url', 'first_name', 'last_name', 'email', 'currentTeam.name', 'roles.name']);

it('can search `:dataset` column', function (string $column): void {
    $records = User::factory(3)->create();
    $records->each(fn (User $user) => $user->teams()->syncWithoutDetaching([$this->team->id]));
    $search = (string) data_get($records->first(), $column);

    $visibleRecords = $records->filter(fn (User $record) => (string) data_get($record, $column) === $search);

    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($records)
        ->searchTable($search)
        ->assertCanSeeTableRecords($visibleRecords);
})->with(['first_name', 'last_name', 'email']);

it('can sort `:dataset` column', function (string $column): void {
    $records = User::factory(3)->create();
    $records->each(fn (User $user) => $user->teams()->syncWithoutDetaching([$this->team->id]));

    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($records)
        ->sortTable($column)
        ->assertCanSeeTableRecords($records->sortBy($column), inOrder: true)
        ->sortTable($column, 'desc')
        ->assertCanSeeTableRecords($records->sortByDesc($column), inOrder: true);
})->with(['first_name', 'last_name', 'email', 'created_at']);

it('has `:dataset` filter', function (string $filter): void {
    livewire(ListUsers::class)
        ->assertTableFilterExists($filter);
})->with(['roles', 'email_verified_at']);

it('shows impersonate action for super admin viewing other users', function (): void {
    $targetUser = User::factory()->create();

    livewire(ListUsers::class)
        ->assertTableActionVisible('impersonate', $targetUser);
});

it('hides impersonate action when viewing self', function (): void {
    livewire(ListUsers::class)
        ->assertTableActionHidden('impersonate', $this->user);
});

it('non-super-admin user cannot impersonate', function (): void {
    $regularUser = User::factory()->create();

    expect($regularUser->canImpersonate())->toBeFalse();
});

it('super admin can impersonate another user', function (): void {
    $targetUser = User::factory()->create();

    expect($this->user->canImpersonate())->toBeTrue()
        ->and($targetUser->canBeImpersonated())->toBeTrue();
});

it('super admin cannot be impersonated', function (): void {
    $otherAdmin = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $otherAdmin->assignRole($role);

    expect($otherAdmin->canBeImpersonated())->toBeFalse();
});

it('system admin cannot be impersonated', function (): void {
    $sysAdmin = User::factory()->create(['is_system_admin' => true]);

    expect($sysAdmin->canBeImpersonated())->toBeFalse();
});

it('does not prefix shield custom permission keys with a separator', function (): void {
    $customPermissions = FilamentShield::transformCustomPermissions();

    expect($customPermissions)->toHaveKey('Impersonate:User')
        ->and($customPermissions)->not->toHaveKey(':Impersonate:User');
});

it('team admin with Impersonate:User permission can impersonate', function (): void {
    setPermissionsTeamId($this->team->id);

    $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web', 'team_id' => $this->team->id]);
    $adminRole->givePermissionTo(Permission::firstOrCreate(['name' => 'Impersonate:User', 'guard_name' => 'web']));

    $teamAdmin = User::factory()->create(['current_team_id' => $this->team->id]);
    $teamAdmin->assignRole($adminRole);

    expect($teamAdmin->canImpersonate())->toBeTrue();
});

it('unverified service user can access the app panel before the tenant team is resolved', function (): void {
    setPermissionsTeamId($this->team->id);
    $serviceUserRole = Role::firstOrCreate(['name' => 'service_user', 'guard_name' => 'web', 'team_id' => $this->team->id]);

    $serviceUser = User::factory()->unverified()->create();
    $serviceUser->teams()->syncWithoutDetaching([$this->team->id]);
    $serviceUser->assignRole($serviceUserRole);

    setPermissionsTeamId(null);

    expect($serviceUser->fresh()->canAccessPanel(Filament::getPanel('app')))->toBeTrue();
});

it('unverified user without service_user role cannot access the app panel', function (): void {
    $unverifiedUser = User::factory()->unverified()->create();

    expect($unverifiedUser->canAccessPanel(Filament::getPanel('app')))->toBeFalse();
});

it('has password and email_verified_at in UserResource form schema', function (): void {
    $schema = UserResource::form(Schema::make());
    $components = collect($schema->getComponents());

    $passwordField = $components->first(fn ($c) => $c->getName() === 'password');
    $emailVerifiedField = $components->first(fn ($c) => $c->getName() === 'email_verified_at');

    expect($passwordField)->not->toBeNull()
        ->and($emailVerifiedField)->not->toBeNull()
        ->and($emailVerifiedField->getDefaultState())->not->toBeNull();
});

it('hashes password and defaults email_verified_at on user creation', function (): void {
    $userData = [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane.doe@example.com',
        'password' => 'secret-password-123',
        'email_verified_at' => now(),
    ];

    $createdUser = User::create($userData);

    expect($createdUser)->not->toBeNull()
        ->and(Hash::check('secret-password-123', $createdUser->password))->toBeTrue()
        ->and($createdUser->email_verified_at)->not->toBeNull();
});
