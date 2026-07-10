<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use Database\Seeders\LocalSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds the functional teams', function () {
    User::factory()->create(['is_system_admin' => true]);
    $this->seed(TeamSeeder::class);

    $teams = [
        'Frontline',
        'Assessment',
        'Drug & Alcohol',
        'Spiritual Counselling',
        'Education & Outreach',
        'Aftercare',
        'Safeguarding',
        'Fundraising',
        'Management',
    ];

    foreach ($teams as $teamName) {
        expect(Team::where('name', $teamName)->exists())->toBeTrue();
    }

    expect(Team::where('personal_team', false)->count())->toBe(count($teams));
});

it('seeds the functional teams via local seeder', function () {
    // LocalSeeder skips if not in local environment, so we mock it or ensure we are in local.
    app()->detectEnvironment(fn () => 'local');

    User::factory()->create(['email' => 'manuk.minasyan1@gmail.com']);
    $this->seed(LocalSeeder::class);

    $teams = [
        'Frontline',
        'Assessment',
        'Drug & Alcohol',
        'Spiritual Counselling',
        'Education & Outreach',
        'Aftercare',
        'Safeguarding',
        'Fundraising',
        'Management',
    ];

    foreach ($teams as $teamName) {
        expect(Team::where('name', $teamName)->exists())->toBeTrue();
    }
});
