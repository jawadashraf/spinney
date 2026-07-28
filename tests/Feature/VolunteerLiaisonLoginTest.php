<?php

declare(strict_types=1);

use App\Filament\Pages\Auth\Login;
use App\Http\Middleware\EnsureVolunteerWorkHours;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Zap\Enums\Frequency;
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
    $this->liaisonRole = Role::firstOrCreate([
        'name' => 'liaison',
        'guard_name' => 'web',
        'team_id' => $this->team->id,
    ]);
});

test('normal liaison can log in at any time', function (): void {
    $user = User::factory()->create([
        'email' => 'liaison@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->teams()->attach($this->team);
    $user->assignRole($this->liaisonRole);

    livewire(Login::class)
        ->fillForm([
            'email' => 'liaison@example.com',
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($user);
});

test('volunteer liaison without any initial work hours is blocked from logging in', function (): void {
    Carbon::setTestNow('2026-07-28 10:30:00');

    $user = User::factory()->create([
        'email' => 'new_volunteer@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->teams()->attach($this->team);
    $user->assignRole($this->volunteerRole);

    livewire(Login::class)
        ->fillForm([
            'email' => 'new_volunteer@example.com',
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    $this->assertGuest();
});

test('volunteer liaison is blocked from logging in outside scheduled work hours', function (): void {
    Carbon::setTestNow('2026-07-28 15:00:00');

    $user = User::factory()->create([
        'email' => 'volunteer@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->teams()->attach($this->team);
    $user->assignRole($this->volunteerRole);

    $schedule = $user->schedules()->create([
        'name' => 'Morning Shift',
        'schedule_type' => ScheduleTypes::AVAILABILITY->value,
        'start_date' => '2026-01-01',
        'is_recurring' => true,
        'frequency' => Frequency::WEEKLY->value,
        'frequency_config' => ['days' => ['tuesday']],
        'is_active' => true,
    ]);

    $schedule->periods()->create([
        'date' => '2026-01-01',
        'start_time' => '09:00',
        'end_time' => '12:00',
        'is_available' => true,
    ]);

    livewire(Login::class)
        ->fillForm([
            'email' => 'volunteer@example.com',
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    $this->assertGuest();
});

test('volunteer liaison can log in during active scheduled work hours', function (): void {
    Carbon::setTestNow('2026-07-28 10:30:00');

    $user = User::factory()->create([
        'email' => 'volunteer@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->teams()->attach($this->team);
    $user->assignRole($this->volunteerRole);

    $schedule = $user->schedules()->create([
        'name' => 'Morning Shift',
        'schedule_type' => ScheduleTypes::AVAILABILITY->value,
        'start_date' => '2026-01-01',
        'is_recurring' => true,
        'frequency' => Frequency::WEEKLY->value,
        'frequency_config' => ['days' => ['tuesday']],
        'is_active' => true,
        'metadata' => ['is_approved' => true],
    ]);

    $schedule->periods()->create([
        'date' => '2026-01-01',
        'start_time' => '09:00',
        'end_time' => '12:00',
        'is_available' => true,
    ]);

    livewire(Login::class)
        ->fillForm([
            'email' => 'volunteer@example.com',
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($user);
});

test('middleware logs out volunteer liaison when session is active outside work hours', function (): void {
    Carbon::setTestNow('2026-07-28 15:00:00');

    $user = User::factory()->create();
    $user->teams()->attach($this->team);
    $user->assignRole($this->volunteerRole);

    $request = Request::create('/app', 'GET');
    $request->setLaravelSession(app('session.store'));
    $request->setUserResolver(fn () => $user);

    $middleware = new EnsureVolunteerWorkHours;
    $response = $middleware->handle($request, fn () => response('OK'));

    expect($response->isRedirect())->toBeTrue();
    $this->assertGuest();
});
