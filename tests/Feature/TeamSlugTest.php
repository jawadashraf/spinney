<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;

test('teams have a unique slug generated from their name', function (): void {
    $user = User::factory()->create();
    
    $team = Team::forceCreate([
        'name' => 'Test Team',
        'user_id' => $user->id,
        'personal_team' => true,
    ]);

    expect($team->slug)->toBe('test-team');
});

test('teams use slug for route model binding', function (): void {
    $team = new Team();
    
    expect($team->getRouteKeyName())->toBe('slug');
});

test('team factory generates a slug', function (): void {
    $team = Team::factory()->make(['name' => 'Factory Team']);
    
    expect($team->slug)->toBe('factory-team');
});
