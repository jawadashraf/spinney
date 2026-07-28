<?php

declare(strict_types=1);

use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Zap\Enums\Frequency;
use Zap\Enums\ScheduleTypes;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->team = Team::factory()->create();
    setPermissionsTeamId($this->team->id);
    $this->admin = User::factory()->create(['is_system_admin' => true]);
});

test('admin can render edit user page with schedules relation manager', function (): void {
    $this->actingAs($this->admin);
    $user = User::factory()->create();

    livewire(EditUser::class, [
        'record' => $user->getRouteKey(),
    ])
        ->assertSuccessful();
});

test('user isWithinWorkHours evaluates availability periods accurately', function (): void {
    $user = User::factory()->create();

    $schedule = $user->schedules()->create([
        'name' => 'Weekly Shift',
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
        'start_time' => '10:00',
        'end_time' => '14:00',
        'is_available' => true,
    ]);

    expect($user->isWithinWorkHours(Carbon::parse('2026-07-28 11:30:00')))->toBeTrue();
    expect($user->isWithinWorkHours(Carbon::parse('2026-07-28 09:30:00')))->toBeFalse();
    expect($user->isWithinWorkHours(Carbon::parse('2026-07-28 14:30:00')))->toBeFalse();
    expect($user->isWithinWorkHours(Carbon::parse('2026-07-29 11:30:00')))->toBeFalse();
});
