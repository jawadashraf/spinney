<?php

declare(strict_types=1);

use App\Filament\Pages\Auth\Login;
use App\Filament\Resources\Calls\CallResource;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\ViewUser;
use App\Models\Call;
use App\Models\Team;
use App\Models\User;
use App\Notifications\CallsDueDigestNotification;
use App\Support\TeamManagers;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();
    setPermissionsTeamId($this->team->id);

    // Managers and admins manage staff accounts, as they do on the live system.
    $userPermissions = collect(['ViewAny:User', 'View:User', 'Update:User'])
        ->map(fn (string $name): Permission => Permission::findOrCreate($name, 'web'));
    Role::query()->where('team_id', $this->team->id)->whereIn('name', ['admin', 'manager'])
        ->each(fn (Role $role): Role => $role->givePermissionTo($userPermissions));

    Filament::setCurrentPanel(Filament::getPanel('app'));
});

function signInToPanel(User $user, Team $team): void
{
    actingAs($user);
    Filament::setTenant($team);
}

function staffMember(Team $team, string $role, array $attributes = []): User
{
    $user = User::factory()->create(['current_team_id' => $team->id, ...$attributes]);
    $user->teams()->syncWithoutDetaching([$team->id]);
    $user->assignRole($role);

    return $user;
}

function addSession(User $user): void
{
    DB::table('sessions')->insert([
        'id' => Str::random(40),
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'payload' => '',
        'last_activity' => now()->timestamp,
    ]);
}

it('deactivates a staff member and ends their open sessions and API tokens', function () {
    config(['session.driver' => 'database']);
    $manager = staffMember($this->team, 'manager');
    $liaison = staffMember($this->team, 'liaison', ['remember_token' => 'old-remember-token']);
    $liaison->createToken('integration');
    addSession($liaison);
    addSession($manager);
    signInToPanel($manager, $this->team);

    livewire(ListUsers::class)
        ->callAction(TestAction::make('deactivate')->table($liaison))
        ->assertNotified($liaison->name.' has been deactivated');

    expect($liaison->fresh())
        ->deactivated_at->not->toBeNull()
        ->remember_token->not->toBe('old-remember-token');
    expect(DB::table('sessions')->where('user_id', $liaison->id)->exists())->toBeFalse()
        ->and(DB::table('sessions')->where('user_id', $manager->id)->exists())->toBeTrue()
        ->and($liaison->tokens()->exists())->toBeFalse();
});

it('reactivates a deactivated staff member', function () {
    signInToPanel(staffMember($this->team, 'manager'), $this->team);
    $liaison = staffMember($this->team, 'liaison', ['deactivated_at' => now()]);

    livewire(ViewUser::class, ['record' => $liaison->id])
        ->assertActionHidden('deactivate')
        ->callAction('reactivate')
        ->assertNotified($liaison->name.' has been reactivated');

    expect($liaison->fresh()->deactivated_at)->toBeNull();
});

it('refuses to sign in a deactivated staff member who gives the right password', function () {
    staffMember($this->team, 'liaison', ['email' => 'gone@example.com', 'password' => 'password', 'deactivated_at' => now()]);

    livewire(Login::class)
        ->fillForm(['email' => 'gone@example.com', 'password' => 'password'])
        ->call('authenticate')
        ->assertHasFormErrors(['email' => 'This account has been deactivated. Please contact your manager.']);

    $this->assertGuest();
});

it('keeps a deactivated staff member out of every panel and impersonation', function () {
    $liaison = staffMember($this->team, 'liaison', ['deactivated_at' => now()]);

    expect($liaison->canAccessPanel(Filament::getPanel('app')))->toBeFalse()
        ->and($liaison->forceFill(['is_system_admin' => true])->canAccessPanel(Filament::getPanel('sysadmin')))->toBeFalse()
        ->and($liaison->canBeImpersonated())->toBeFalse();
});

it('decides who can deactivate whom', function (string $actorRole, string $staffRole, bool $allowed) {
    $actor = staffMember($this->team, $actorRole);
    $staff = staffMember($this->team, $staffRole);

    expect($actor->canChangeAccountStatusOf($staff))->toBe($allowed);
})->with([
    'manager deactivating a liaison' => ['manager', 'liaison', true],
    'manager deactivating an admin' => ['manager', 'admin', false],
    'admin deactivating an admin' => ['admin', 'admin', true],
    'admin deactivating a super admin' => ['admin', 'super_admin', false],
    'liaison deactivating a liaison' => ['liaison', 'liaison', false],
]);

it('does not let anyone deactivate themselves', function () {
    $manager = staffMember($this->team, 'manager');
    signInToPanel($manager, $this->team);

    livewire(ListUsers::class)
        ->assertActionHidden(TestAction::make('deactivate')->table($manager));
});

it('lists only deactivated staff when filtered by status', function () {
    signInToPanel(staffMember($this->team, 'manager'), $this->team);
    $active = staffMember($this->team, 'liaison');
    $deactivated = staffMember($this->team, 'liaison', ['deactivated_at' => now()]);

    livewire(ListUsers::class)
        ->filterTable('status', false)
        ->assertCanSeeTableRecords([$deactivated])
        ->assertCanNotSeeTableRecords([$active]);
});

it('leaves deactivated staff out of call assignment but keeps them on calls they already hold', function () {
    $active = staffMember($this->team, 'liaison');
    $deactivated = staffMember($this->team, 'liaison', ['deactivated_at' => now()]);

    expect(CallResource::assignableUserOptions())->toHaveKey($active->id)->not->toHaveKey($deactivated->id)
        ->and(CallResource::scopeAssignableUsers(User::query(), $deactivated->id)->pluck('id')->all())->toContain($deactivated->id)
        ->and(CallResource::callStaffOptions())->toHaveKey($deactivated->id);
});

it('stops sending manager notifications to a deactivated manager', function () {
    $active = staffMember($this->team, 'manager');
    $deactivated = staffMember($this->team, 'manager', ['deactivated_at' => now()]);

    expect(TeamManagers::for($this->team->id)->modelKeys())->toContain($active->id)->not->toContain($deactivated->id);
});

it('stops sending the calls-due email to a deactivated liaison', function () {
    Notification::fake();
    $deactivated = staffMember($this->team, 'liaison', ['deactivated_at' => now()]);
    Call::factory()->assignedTo($deactivated)->create(['team_id' => $this->team->id, 'due_at' => now()->subHour()]);

    $this->artisan('calls:send-reminders')->assertSuccessful();

    Notification::assertNotSentTo($deactivated, CallsDueDigestNotification::class);
});
